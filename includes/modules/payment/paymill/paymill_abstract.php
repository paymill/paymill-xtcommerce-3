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

    var $code, $title, $description = '', $enabled, $privateKey, $logging, $fastCheckoutFlag, $order_status, $publicKey;
    var $bridgeUrl = 'https://bridge.paymill.com/';
    var $apiUrl = 'https://api.paymill.com/v2/';
    var $version = '1.3.2';
    var $api_version = '2';


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
        $this->description = '';
        $this->description .= '<p style="font-weight: bold;">'.$this->version.'</p>';
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

            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . (int) $zone_id . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
            while ($check = xtc_db_fetch_array($check_query)) {
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
        
        if (empty($this->privateKey) || empty($this->publicKey)) {
            $this->enabled = false;
        }
    }

    function pre_confirmation_check()
    {
        return false;
    }

    function get_error()
    {
        global $_GET;
        $error = '';
        
        if (isset($_GET['error'])) {
            $error = urldecode($_GET['error']);
        }

        $error_text['error'] = utf8_decode(constant('PAYMILL_'.$error));

        return $error_text;
    }

    function javascript_validation()
    {
        return false;
    }

    function selection()
    {
        return array(
            'id'     => $this->code,
            'module' => $this->public_title      
        );
    }

    function confirmation()
    {
        global $order;
        $_SESSION['paymill']['amount'] = $this->format_raw($order->info['total']);
        return array(
            'fields' => array(
                array(
                    'title' => '',
                    'field' => '<link rel="stylesheet" type="text/css" href="ext/modules/payment/paymill/public/css/paymill.css" />'
                ),
                array(
                    'title' => '',
                    'field' => '<script type="text/javascript">var PAYMILL_PUBLIC_KEY = "' . $this->publicKey . '";</script>'
                ),
                array(
                    'title' => '',
                    'field' => '<script type="text/javascript" src="' . $this->bridgeUrl . '"></script>'
                ),
            )
        );
    }

    function process_button()
    {
        return false;
    }
    
    function before_process()
    {
        global $order;
        
        $_SESSION['paymill_identifier'] = time();

        $this->paymentProcessor->setAmount((int) $_SESSION['paymill']['amount']);
        $this->paymentProcessor->setApiUrl((string) $this->apiUrl);
        $this->paymentProcessor->setCurrency((string) strtoupper($order->info['currency']));
        $this->paymentProcessor->setDescription(utf8_encode((string) STORE_NAME . ' ' . $order->customer['lastname'] . ', ' . $order->customer['firstname']));
        $this->paymentProcessor->setEmail((string) $order->customer['email_address']);
        $this->paymentProcessor->setName($order->customer['lastname'] . ', ' . $order->customer['firstname']);
        $this->paymentProcessor->setPrivateKey((string) $this->privateKey);
        $this->paymentProcessor->setToken((string) $_POST['paymill_token']);
        $this->paymentProcessor->setLogger($this);
        $this->paymentProcessor->setSource($this->version . '_' . str_replace(' ', '_', PROJECT_VERSION));

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
            unset($_SESSION['paymill_identifier']);
            $errorCode = $this->paymentProcessor->getErrorCode();
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'step=step2&payment_error=' . $this->code . '&error='.$errorCode, 'SSL', true, false));
        }
        
        if ($this->fastCheckoutFlag) {
            $this->savePayment();
        } else {
            $this->saveClient();
        }
        
        unset($_SESSION['paymill_identifier']);
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

        xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        if ($this->order_status) {
            xtc_db_query("UPDATE " . TABLE_ORDERS . " SET orders_status='" . $this->order_status . "' WHERE orders_id='" . $insert_id . "'");
        }
        
        unset($_SESSION['paymill']);
    }

    function remove()
    {
        xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");
    }

    function getOrderStatusTransactionID()
    {
        $check_query = xtc_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Paymill [Transactions]' limit 1");

        if (xtc_db_num_rows($check_query) < 1) {
            $status_query = xtc_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
            $status = xtc_db_fetch_array($status_query);

            $status_id = $status['status_id'] + 1;

            $languages = xtc_get_languages();

            foreach ($languages as $lang) {
                xtc_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $lang['id'] . "', 'Paymill [Transactions]')");
            }

            $flags_query = xtc_db_query("describe " . TABLE_ORDERS_STATUS . " public_flag");
            if (xtc_db_num_rows($flags_query) == 1) {
                xtc_db_query("update " . TABLE_ORDERS_STATUS . " set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $status_id . "'");
            }
        } else {
            $check = xtc_db_fetch_array($check_query);

            $status_id = $check['orders_status_id'];
        }

        return $status_id;
    }

    function log($messageInfo, $debugInfo)
    {
        if ($this->logging) {
            if (array_key_exists('paymill_identifier', $_SESSION)) {
                 xtc_db_query("INSERT INTO `pi_paymill_logging` "
                            . "(debug, message, identifier) "
                            . "VALUES('" 
                              . xtc_db_input($debugInfo) . "', '" 
                              . xtc_db_input($messageInfo) . "', '" 
                              . xtc_db_input($_SESSION['paymill_identifier']) 
                            . "')"
                );
            }
        }
    }

    function format_raw($number)
    {
        return number_format(round($number, 2), 2, '', '');
    }

    function install()
    {
        if (xtc_db_num_rows(xtc_db_query("show columns from admin_access like 'paymill_%'")) == 0) {
            xtc_db_query("ALTER TABLE admin_access ADD paymill_logging INT(1) NOT NULL DEFAULT '0'");
            xtc_db_query("ALTER TABLE admin_access ADD paymill_log INT(1) NOT NULL DEFAULT '0'");
        }
        
        xtc_db_query("UPDATE admin_access SET paymill_logging = '1', paymill_log = '1' WHERE customers_id= '1' OR customers_id = 'groups'");
        
        xtc_db_query("DROP TABLE IF EXISTS `pi_paymill_logging`");
        
        xtc_db_query(
            "CREATE TABLE IF NOT EXISTS `pi_paymill_logging` ("
          . "`id` int(11) NOT NULL AUTO_INCREMENT,"
          . "`identifier` text NOT NULL,"
          . "`debug` text NOT NULL,"
          . "`message` text NOT NULL,"
          . "`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
          . "PRIMARY KEY (`id`)"
        . ") AUTO_INCREMENT=1"
        );
        
        xtc_db_query(
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
