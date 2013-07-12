<?php

require_once(dirname(dirname(__FILE__)) . '/lib/Services/Paymill/PaymentProcessor.php');
require_once(dirname(dirname(__FILE__)) . '/lib/Services/Paymill/LoggingInterface.php');

/**
 * Paymill payment plugin
 */
class paymill implements Services_Paymill_LoggingInterface
{

    var $code, $title, $description = '', $enabled, $privateKey;
    var $bridgeUrl = 'https://bridge.paymill.com/';
    var $apiUrl = 'https://api.paymill.com/v2/';
    var $logging;
    var $differentAmount = 0;

    function pre_confirmation_check()
    {
        global $order, $xtPrice;
        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 && $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1) {
            $total = $order->info['total'] + $order->info['tax'];
        } else {
            $total = $order->info['total'];
        }

        if ($_SESSION['currency'] == $order->info['currency']) {
            $amount = round($total, $xtPrice->get_decimal_places($order->info['currency']));
        } else {
            $amount = round(
                    $xtPrice->xtcCalculateCurrEx($total, $order->info['currency']), $xtPrice->get_decimal_places($order->info['currency'])
            );
        }

        $_SESSION['paymill_token'] = $_POST['paymill_token'];
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
        return false;
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

    function update_status()
    {
        return false;
    }

    function javascript_validation()
    {
        return '';
    }

    function payment_action()
    {
        global $order, $xtPrice;

        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 && $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1) {
            $total = $order->info['total'] + $order->info['tax'];
        } else {
            $total = $order->info['total'];
        }

        if ($_SESSION['currency'] == $order->info['currency']) {
            $amount = round($total, $xtPrice->get_decimal_places($order->info['currency']));
        } else {
            $amount = round($xtPrice->xtcCalculateCurrEx($total, $order->info['currency']), $xtPrice->get_decimal_places($order->info['currency']));
        }

        $paymill = new Services_Paymill_PaymentProcessor();
        $paymill->setAmount((int) (string) ($amount * 100));
        $paymill->setApiUrl((string) $this->apiUrl);
        $paymill->setCurrency((string) strtoupper($order->info['currency']));
        $paymill->setDescription((string) STORE_NAME . ' Bestellnummer: ' . $_SESSION['tmp_oID']);
        $paymill->setEmail((string) $order->customer['email_address']);
        $paymill->setName((string) $order->customer['lastname'] . ', ' . $order->customer['firstname']);
        $paymill->setPrivateKey((string) $this->privateKey);
        $paymill->setToken((string) $_SESSION['paymill_token']);
        $paymill->setLogger($this);
        $paymill->setSource($this->version . '_' . str_replace(' ', '_', PROJECT_VERSION));
        $paymill->setDifferentAmount((int) (string) ($this->differentAmount * 100));

        $result = $paymill->processPayment();

        if (!$result) {
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'step=step2&payment_error=' . $this->code . '&error=200', 'SSL', true, false));
        } else {
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PROCESS, 'SSL', true, false));
        }
    }

    function after_process()
    {
        global $insert_id;

        if ($this->order_status) {
            xtc_db_query("UPDATE " . TABLE_ORDERS . " SET orders_status='" . $this->order_status . "' WHERE orders_id='" . $insert_id . "'");
        }

        return true;
    }

    /**
     * Add the shipping tax to the order object
     *
     * @param order $order
     * @return float
     */
    public function getShippingTaxAmount(order $order)
    {
        return round($order->info['shipping_cost'] * (self::getShippingTaxRate($order) / 100), 2);
    }

    /**
     * Retrieve the shipping tax rate
     *
     * @param order $order
     * @return float
     */
    public function getShippingTaxRate(order $order)
    {
        $shippingClassArray = explode("_", $order->info['shipping_class']);
        $shippingClass = strtoupper($shippingClassArray[0]);
        if (empty($shippingClass)) {
            $shippingTaxRate = 0;
        } else {
            $const = 'MODULE_SHIPPING_' . $shippingClass . '_TAX_CLASS';
            if (defined($const)) {
                $shippingTaxRate = xtc_get_tax_rate(constant($const));
            } else {
                $shippingTaxRate = 0;
            }
        }

        return $shippingTaxRate;
    }

    public function log($messageInfo, $debugInfo)
    {
        if ($this->logging) {
            $logfile = dirname(dirname(__FILE__)) . '/log/log.txt';
            if (file_exists($logfile) && is_writable($logfile)) {
                $handle = fopen($logfile, 'a');
                fwrite($handle, "[" . date(DATE_RFC822) . "] " . $messageInfo . "\n");
                fwrite($handle, "[" . date(DATE_RFC822) . "] " . $debugInfo . "\n");
                fclose($handle);
            }
        }
    }

    private function getDifferentAmount()
    {
        $differenceAmount = $this->differentAmount;
        if(empty($differenceAmount) || !is_numeric($differenceAmount)) {
            $differenceAmount = 0;
        }
        
        return $differenceAmount;
    }
    
}
