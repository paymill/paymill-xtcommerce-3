<?php
require_once('paymill/paymill_abstract.php');

class paymill_cc extends paymill_abstract
{

    function paymill_cc()
    {
        parent::paymill_abstract();
        global $order;

        $this->code = 'paymill_cc';
        $this->version = '1.1.0';
        $this->api_version = '2';
        $this->title = MODULE_PAYMENT_PAYMILL_CC_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_PAYMILL_CC_TEXT_PUBLIC_TITLE;
        
        if (defined('MODULE_PAYMENT_PAYMILL_CC_STATUS')) {
            $this->enabled = ((MODULE_PAYMENT_PAYMILL_CC_STATUS == 'True') ? true : false);
            $this->sort_order = MODULE_PAYMENT_PAYMILL_CC_SORT_ORDER;
            $this->privateKey = trim(MODULE_PAYMENT_PAYMILL_CC_PRIVATEKEY);
            $this->logging = ((MODULE_PAYMENT_PAYMILL_CC_LOGGING == 'True') ? true : false);
            $this->publicKey = MODULE_PAYMENT_PAYMILL_CC_PUBLICKEY;
            $this->fastCheckoutFlag = ((MODULE_PAYMENT_PAYMILL_CC_FASTCHECKOUT == 'True') ? true : false);
            $this->payments = new Services_Paymill_Payments(trim($this->privateKey), $this->apiUrl);
            $this->clients = new Services_Paymill_Clients(trim($this->privateKey), $this->apiUrl);
            if ((int) MODULE_PAYMENT_PAYMILL_CC_ORDER_STATUS_ID > 0) {
                $this->order_status = MODULE_PAYMENT_PAYMILL_CC_ORDER_STATUS_ID;
            }
        }

        if (is_object($order)) $this->update_status();
    }
        
    function getPayment($userId)
    {
        $payment = array(
            'last4' => '',
            'cvc' => '',
            'card_holder' => '',
            'expire_month' => '',
            'expire_year' => '',
            'card_type' => '',
        );
        
        if ($this->fastCheckout->hasCcPaymentId($userId)) {
            $data = $this->fastCheckout->loadFastCheckoutData($userId);
            $payment = $this->payments->getOne($data['paymentID_CC']);
            $payment['last4'] = '************' . $payment['last4'];
            $payment['cvc'] = '***';
        }
        
        return $payment;
    }


    function confirmation()
    {
        global $order;
        
        $confirmation = parent::confirmation();        

        $months_array = array();

        for ($i=1; $i<13; $i++) {
            $months_array[$i] = array(sprintf('%02d', $i), strftime('%B',mktime(0,0,0,$i,1,2000)));
        }

        $today = getdate(); 
        $years_array = array();

        for ($i=$today['year']; $i < $today['year']+10; $i++) {
            $years_array[$i] = array(strftime('%Y', mktime(0, 0, 0, 1 , 1, $i)), strftime('%Y',mktime(0, 0, 0, 1, 1, $i)));
        } 
        
        $payment = $this->getPayment($_SESSION['customer_id']);
        
        $this->fastCheckout->setFastCheckoutFlag($this->fastCheckoutFlag);
        
        $script = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>'
                . '<script type="text/javascript">'
                    . 'var cclogging = "' . MODULE_PAYMENT_PAYMILL_CC_LOGGING . '";'
                    . 'var cc_expiery_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_CC_TEXT_CREDITCARD_EXPIRY_INVALID) . '";'
                    . 'var cc_owner_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_CC_TEXT_CREDITCARD_OWNER_INVALID) . '";'
                    . 'var cc_card_number_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_CC_TEXT_CREDITCARD_CARDNUMBER_INVALID) . '";'
                    . 'var cc_cvc_number_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_CC_TEXT_CREDITCARD_CVC_INVALID) . '";'
                    . 'var brand = "' . $payment['card_type'] . '";'
                    . 'var paymill_total = ' . json_encode($this->format_raw($order->info['total'])) . ';'
                    . 'var paymill_currency = ' . json_encode(strtoupper($order->info['currency'])) . ';'
                    . 'var paymill_cc_months = ' . json_encode($months_array) . ';'
                    . 'var paymill_cc_years = ' . json_encode($years_array) . ';'
                    . 'var paymill_cc_number_val = "' . $payment['last4'] . '";'
                    . 'var paymill_cc_cvc_val = "' . $payment['cvc'] . '";'
                    . 'var paymill_cc_holder_val = "' . $payment['card_holder'] . '";'
                    . 'var paymill_cc_expiry_month_val = "' . $payment['expire_month'] . '";'
                    . 'var paymill_cc_expiry_year_val = "' . $payment['expire_year'] . '";'
                    . 'var paymill_cc_fastcheckout = ' . $this->fastCheckout->canCustomerFastCheckoutCcTemplate($_SESSION['customer_id']) . ';'
                . '</script>'
                . '<script type="text/javascript" src="ext/modules/payment/paymill/public/javascript/cc.js"></script>';
        
        array_push($confirmation['fields'], 
            array(
                'title' => '',
                'field' => $script
            )
        );
        
        array_push($confirmation['fields'], 
            array(
                'title' => MODULE_PAYMENT_PAYMILL_CC_TEXT_CREDITCARD_OWNER,
                'field' => '<span id="card-owner-field"></span>'
            )
        );
                
        array_push($confirmation['fields'], 
            array(
                'title' => MODULE_PAYMENT_PAYMILL_CC_TEXT_CREDITCARD_NUMBER,
                'field' => '<span id="card-number-field"></span><span class="card-icon"></span>'
            )
        );
        
        array_push($confirmation['fields'], 
            array(
                'title' => MODULE_PAYMENT_PAYMILL_CC_TEXT_CREDITCARD_EXPIRY,
                'field' => '<span class="paymill-expiry"><span id="card-expiry-month-field"></span>&nbsp;<span id="card-expiry-year-field"></span></span>'
            )
        );
        
        array_push($confirmation['fields'], 
            array(
                'title' => MODULE_PAYMENT_PAYMILL_CC_TEXT_CREDITCARD_CVC,
                'field' => '<span id="card-cvc-field" class="card-cvc-row"></span>'
            )
        );
        
        array_push($confirmation['fields'], 
            array(
                'title' => '',
                'field' => '<form id="paymill_form" action="' . xtc_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL') . '" method="post" style="display: none;"></form>'
            )
        );

        return $confirmation;
    }

    function check()
    {
        if (!isset($this->_check)) {
            $check_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_PAYMILL_CC_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function install()
    {
        parent::install();
        
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_CC_STATUS', 'True', '6', '1', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_CC_FASTCHECKOUT', 'False', '6', '1', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_CC_SORT_ORDER', '0', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_CC_PRIVATEKEY', '0', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_CC_PUBLICKEY', '0', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_PAYMILL_CC_ORDER_STATUS_ID', '0',  '6', '0', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_CC_LOGGING', 'False', '6', '0', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_PAYMILL_CC_TRANSACTION_ORDER_STATUS_ID', '" . $this->getOrderStatusTransactionID() . "', '6', '0', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_PAYMENT_PAYMILL_CC_ZONE', '0', '6', '2', 'xtc_get_zone_class_title', 'xtc_cfg_pull_down_zone_classes(', now())");
    }

    function keys()
    {
        return array(
            'MODULE_PAYMENT_PAYMILL_CC_STATUS',
            'MODULE_PAYMENT_PAYMILL_CC_FASTCHECKOUT',
            'MODULE_PAYMENT_PAYMILL_CC_PRIVATEKEY',
            'MODULE_PAYMENT_PAYMILL_CC_PUBLICKEY',
            'MODULE_PAYMENT_PAYMILL_CC_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYMILL_CC_TRANSACTION_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYMILL_CC_ZONE',
            'MODULE_PAYMENT_PAYMILL_CC_LOGGING',
            'MODULE_PAYMENT_PAYMILL_CC_SORT_ORDER'
        );
    }
}
?>
