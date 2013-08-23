<?php

require_once(DIR_FS_CATALOG . 'ext/modules/payment/paymill/lib/Services/Paymill/PaymentProcessor.php');
require_once(DIR_FS_CATALOG . 'ext/modules/payment/paymill/lib/Services/Paymill/LoggingInterface.php');
require_once(DIR_FS_CATALOG . 'ext/modules/payment/paymill/lib/Services/Paymill/Payments.php');
require_once(DIR_FS_CATALOG . 'ext/modules/payment/paymill/lib/Services/Paymill/Clients.php');
require_once(DIR_FS_CATALOG . 'ext/modules/payment/paymill/FastCheckout.php');

/**
 * Paymill payment plugin
 */
class paymill_abstract implements Services_Paymill_LoggingInterface
{

    var $code, $title, $description = '', $enabled, $privateKey, $logging, $fastCheckoutFlag;
    var $bridgeUrl = 'https://bridge.paymill.com/';
    var $apiUrl = 'https://api.paymill.com/v2/';
    
    /**
     * @var FastCheckout
     */
    var $fastCheckout;
    
    /**
     * @var Services_Paymill_Payments
     */
    var $payments;
    
    /**
     *
     * @var Services_Paymill_PaymentProcessor
     */
    var $paymentProcessor;

    function paymill_abstract()
    {
        $this->fastCheckout = new FastCheckout();
        $this->paymentProcessor = new Services_Paymill_PaymentProcessor();
    }
    
    /**
     * @return FastCheckout
     */
    function getFastCheckout()
    {
        return $this->fastCheckout;
    }
    
    function update_status()
    {
        global $order;

        if (get_class($this) == 'paymill_cc') {
            $zone_id = MODULE_PAYMENT_PAYMILL_CC_ZONE;
        } elseif (get_class($this) == 'paymill_elv') {
            $zone_id = MODULE_PAYMENT_PAYMILL_ELV_ZONE;
        } else {
            $zone_id = 0;
        }

        if (($this->enabled == true) && ((int) $zone_id > 0)) {
            $check_flag = false;

            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . (int) $zone_id . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    function pre_confirmation_check()
    {
        global $oscTemplate;

        $oscTemplate->addBlock('<link rel="stylesheet" type="text/css" href="ext/modules/payment/paymill/public/css/paymill.css" />', 'header_tags');
        $oscTemplate->addBlock('<script type="text/javascript">var PAYMILL_PUBLIC_KEY = "' . $this->publicKey . '";</script>', 'header_tags');
        $oscTemplate->addBlock('<script type="text/javascript" src="' . $this->bridgeUrl . '"></script>', 'header_tags');
    }

    function get_error()
    {
        global $_GET;
        $error = '';

        if (isset($_GET['error'])) {
            $error = urldecode($_GET['error']);
        }

        switch ($error) {
            case '100':
                $error_text['error'] = utf8_decode(MODULE_PAYMENT_PAYMILL_TEXT_ERROR_100);
                break;
            case '200':
                $error_text['error'] = utf8_decode(MODULE_PAYMENT_PAYMILL_TEXT_ERROR_200);
                break;
        }

        return $error_text;
    }

    function javascript_validation()
    {
        return false;
    }

    function selection()
    {
        return array('id' => $this->code,
            'module' => $this->public_title);
    }

    function confirmation()
    {
        return false;
    }

    function process_button()
    {
        return false;
    }

    function before_process()
    {
        global $order;

        $this->paymentProcessor->setAmount((int) $this->format_raw($order->info['total']));
        $this->paymentProcessor->setApiUrl((string) $this->apiUrl);
        $this->paymentProcessor->setCurrency((string) strtoupper($order->info['currency']));
        $this->paymentProcessor->setDescription((string) STORE_NAME);
        $this->paymentProcessor->setEmail((string) $order->customer['email_address']);
        $this->paymentProcessor->setName((string) $order->customer['lastname'] . ', ' . $order->customer['firstname']);
        $this->paymentProcessor->setPrivateKey((string) $this->privateKey);
        $this->paymentProcessor->setToken((string) $_POST['paymill_token']);
        $this->paymentProcessor->setLogger($this);
        $this->paymentProcessor->setSource($this->version . '_OSCOM_' . tep_get_version());

        $this->fastCheckout->setFastCheckoutFlag($this->fastCheckoutFlag);
        
        if ($_POST['paymill_token'] === 'dummyToken') {
            $this->fastCheckout();
        }
        
        $data = $this->fastCheckout->loadFastCheckoutData($_SESSION['customer_id']);
        if (!empty($data['clientID'])) {
            $this->existingClient($data);
        }
        
        $result = $this->paymentProcessor->processPayment();
        $_SESSION['paymill']['transaction_id'] = $this->paymentProcessor->getTransactionId();

        if (!$result) {
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false) . '?step=step2&payment_error=' . $this->code . '&error=200');
        }
        
        if ($this->fastCheckoutFlag) {
            $this->savePayment();
        } else {
            $this->saveClient();
        }
    }

    function existingClient($data)
    {
        global $order;
        
        $client = $this->clients->getOne($data['clientID']);
        if ($client['email'] !== $order->customer['email_address']) {
            $this->clients->update(
                array(
                    'id' => $data['clientID'],
                    'email' => $order->customer['email_address']
                )
            );
        }

        $this->paymentProcessor->setClientId($client['id']);
    }
    
    function fastCheckout()
    {
        if ($this->fastCheckout->canCustomerFastCheckoutCc($_SESSION['customer_id']) && $this->code === 'paymill_cc') {
            $data = $this->fastCheckout->loadFastCheckoutData($_SESSION['customer_id']);
            if (!empty($data['paymentID_CC'])) {
                $this->paymentProcessor->setPaymentId($data['paymentID_CC']);
            }
        }
        
        if ($this->fastCheckout->canCustomerFastCheckoutElv($_SESSION['customer_id']) && $this->code === 'paymill_elv') {
            $data = $this->fastCheckout->loadFastCheckoutData($_SESSION['customer_id']);
            
            if (!empty($data['paymentID_ELV'])) {
                $this->paymentProcessor->setPaymentId($data['paymentID_ELV']);
            }
        }
    }

    function savePayment()
    {
        if ($this->code === 'paymill_cc') {
            $this->fastCheckout->saveCcIds(
                $_SESSION['customer_id'], $this->paymentProcessor->getClientId(), $this->paymentProcessor->getPaymentId()
            );
        }

        if ($this->code === 'paymill_elv') {
            $this->fastCheckout->saveElvIds(
                $_SESSION['customer_id'], $this->paymentProcessor->getClientId(), $this->paymentProcessor->getPaymentId()
            );
        }
    }
    
    function saveClient()
    {
        if ($this->code === 'paymill_cc') {
            $this->fastCheckout->saveCcIds(
                $_SESSION['customer_id'], $this->paymentProcessor->getClientId(), ''
            );
        }

        if ($this->code === 'paymill_elv') {
            $this->fastCheckout->saveElvIds(
                $_SESSION['customer_id'], $this->paymentProcessor->getClientId(), ''
            );
        }
    }
    
    function after_process()
    {
        global $order, $insert_id;

        if (get_class($this) == 'paymill_cc') {
            $order_status_id = MODULE_PAYMENT_PAYMILL_CC_TRANSACTION_ORDER_STATUS_ID;
        } elseif (get_class($this) == 'paymill_elv') {
            $order_status_id = MODULE_PAYMENT_PAYMILL_ELV_TRANSACTION_ORDER_STATUS_ID;
        } else {
            $order_status_id = $order->info['order_status'];
        }

        $sql_data_array = array('orders_id' => $insert_id,
            'orders_status_id' => $order_status_id,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => 'Payment approved, Transaction ID: ' . $_SESSION['paymill']['transaction_id']);

        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        unset($_SESSION['paymill']);
    }

    function remove()
    {
        tep_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");
    }

    function getOrderStatusTransactionID()
    {
        $check_query = tep_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Paymill [Transactions]' limit 1");

        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);

            $status_id = $status['status_id'] + 1;

            $languages = tep_get_languages();

            foreach ($languages as $lang) {
                tep_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $lang['id'] . "', 'Paymill [Transactions]')");
            }

            $flags_query = tep_db_query("describe " . TABLE_ORDERS_STATUS . " public_flag");
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("update " . TABLE_ORDERS_STATUS . " set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);

            $status_id = $check['orders_status_id'];
        }

        return $status_id;
    }

    function log($messageInfo, $debugInfo)
    {
        if ($this->logging) {
            $logfile = dirname(__FILE__) . '/log.txt';
            if (file_exists($logfile) && is_writable($logfile)) {
                $handle = fopen($logfile, 'a');
                fwrite($handle, "[" . date(DATE_RFC822) . "] " . $messageInfo . "\n");
                fwrite($handle, "[" . date(DATE_RFC822) . "] " . $debugInfo . "\n");
                fclose($handle);
            }
        }
    }

    function format_raw($number, $currency_code = '', $currency_value = '')
    {
        global $currencies, $currency;

        if (empty($currency_code) || !$currencies->is_set($currency_code)) {
            $currency_code = $currency;
        }

        if (empty($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '', '');
    }

    function install()
    {
        tep_db_query(
            "CREATE TABLE IF NOT EXISTS `pi_paymill_fastcheckout` ("
           . "`userID` varchar(100),"
           . "`clientID` varchar(100),"
           . "`paymentID_CC` varchar(100),"
           . "`paymentID_ELV` varchar(100),"
           . "PRIMARY KEY (`userID`)"
         . ")"
        );
    }
    
}

?>
