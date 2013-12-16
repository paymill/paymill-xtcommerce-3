<?php
require_once('paymill/paymill_abstract.php');

class paymill_elv extends paymill_abstract
{

    function paymill_elv()
    {
        parent::paymill_abstract();
        $this->fastCheckout = new FastCheckout(trim(MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY));
        global $order;

        $this->code = 'paymill_elv';
        $this->title = MODULE_PAYMENT_PAYMILL_ELV_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_PAYMILL_ELV_TEXT_PUBLIC_TITLE;

        if (defined('MODULE_PAYMENT_PAYMILL_ELV_STATUS')) {
            $this->enabled = ((MODULE_PAYMENT_PAYMILL_ELV_STATUS == 'True') ? true : false);
            $this->sort_order = MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER;
            $this->privateKey = trim(MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY);
            $this->logging = ((MODULE_PAYMENT_PAYMILL_ELV_LOGGING == 'True') ? true : false);
            $this->webHooksEnabled = ((MODULE_PAYMENT_PAYMILL_ELV_WEBHOOKS == 'True') ? true : false);
            $this->publicKey = MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY;
            $this->fastCheckoutFlag = ((MODULE_PAYMENT_PAYMILL_ELV_FASTCHECKOUT == 'True') ? true : false);
            $this->payments = new Services_Paymill_Payments($this->privateKey, $this->apiUrl);
            $this->clients = new Services_Paymill_Clients(trim($this->privateKey), $this->apiUrl);
            if ((int) MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID > 0) {
                $this->order_status = MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID;
            }
            
            if ($this->logging) {
                $this->description .= '<p><a href="' . xtc_href_link('paymill_logging.php') . '">' . MODULE_PAYMENT_PAYMILL_ELV_DESCRIPTION . '</a></p>';
            }


            if ($this->webHooksEnabled) {
                $type = 'ELV';
                $this->displayWebhookButton($type);
            }
        }

        if (is_object($order)) $this->update_status();
    }
    
    function selection()
    {
        $selection = parent::selection();
        return $selection;
    }
        
    function getPayment($userId)
    {
        $payment = array(
            'code' => '',
            'holder' => '',
            'account' => ''
        );
        
        if ($this->fastCheckout->hasElvPaymentId($userId)) {
            $data = $this->fastCheckout->loadFastCheckoutData($userId);
            $payment = $this->payments->getOne($data['paymentID_ELV']);
        }
        
        return $payment;
    }
    
    function confirmation()
    {
        global $order;
        
        $confirmation = parent::confirmation();

        $this->fastCheckout->setFastCheckoutFlag($this->fastCheckoutFlag);
        $payment = $this->getPayment($_SESSION['customer_id']);

        
        $script = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>'
                . '<script type="text/javascript">'
                . 'var elvlogging = "' . MODULE_PAYMENT_PAYMILL_ELV_LOGGING . '";'
                . 'var elv_account_number_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_INVALID) . '";'
                . 'var elv_bank_code_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_BANKCODE_INVALID) . '";'
                . 'var elv_bank_owner_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_HOLDER_INVALID) . '";'
                . 'var paymill_account_name = ' . json_encode($order->billing['firstname'] . ' ' . $order->billing['lastname']) . ';'
                . 'var paymill_elv_code = "' . $payment['code'] . '";'
                . 'var paymill_elv_holder = "' . utf8_decode($payment['holder']) . '";'
                . 'var paymill_elv_account = "' . $payment['account'] . '";'
                . 'var paymill_elv_fastcheckout = ' . ($this->fastCheckout->canCustomerFastCheckoutElv($_SESSION['customer_id']) ? 'true': 'false') . ';'
                . 'var checkout_payment_link = "' . xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'step=step2&payment_error=' . $this->code . '&error=300', 'SSL', true, false) . '";'
                . '</script>'
                . '<script type="text/javascript" src="ext/modules/payment/paymill/public/javascript/elv.js"></script>';
        
        array_push($confirmation['fields'], 
            array(
                'field' => $script
            )
        );
        
        array_push($confirmation['fields'], 
            array(
                'title' => '<div class="paymill-label-field">' . MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_HOLDER . '</div>',
                'field' => '<span id="account-name-field"></span><span id="elv-holder-error" class="paymill-error"></span>'
            )
        );
        
        array_push($confirmation['fields'], 
            array(
                'title' => '<div class="paymill-label-field">' . MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT . '</div>',
                'field' => '<span id="account-number-field"></span><span id="elv-account-error" class="paymill-error"></span>'
            )
        );
        
        array_push($confirmation['fields'], 
            array(
                'title' => '<div class="paymill-label-field">' . MODULE_PAYMENT_PAYMILL_ELV_TEXT_BANKCODE . '</div>',
                'field' => '<span id="bank-code-field"></span><span id="elv-bankcode-error" class="paymill-error"></span>'
            )
        );       
        
        array_push($confirmation['fields'], 
            array(
                'field' => '<form id="paymill_form" action="' . xtc_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL') . '" method="post" style="display: none;"></form>'
            )
        );

        return $confirmation;
    }

    function check()
    {
        if (!isset($this->_check)) {
            $check_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_PAYMILL_ELV_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function install()
    {
        parent::install();

        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_STATUS', 'True', '6', '1', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_FASTCHECKOUT', 'False', '6', '1', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_WEBHOOKS', 'False', '6', '1', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER', '0', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY', '0', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY', '0', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID', '0',  '6', '0', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_LOGGING', 'False', '6', '0', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_PAYMILL_ELV_TRANSACTION_ORDER_STATUS_ID', '" . $this->getOrderStatusTransactionID() . "', '6', '0', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_PAYMILL_ELV_ALLOWED', '', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_PAYMENT_PAYMILL_ELV_ZONE', '0', '6', '2', 'xtc_get_zone_class_title', 'xtc_cfg_pull_down_zone_classes(', now())");
    }

    function keys()
    {
        return array(
            'MODULE_PAYMENT_PAYMILL_ELV_STATUS',
            'MODULE_PAYMENT_PAYMILL_ELV_FASTCHECKOUT',
            'MODULE_PAYMENT_PAYMILL_ELV_WEBHOOKS',
            'MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY',
            'MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY',
            'MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYMILL_ELV_TRANSACTION_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYMILL_ELV_ZONE',
            'MODULE_PAYMENT_PAYMILL_ELV_LOGGING',
            'MODULE_PAYMENT_PAYMILL_ELV_ALLOWED',
            'MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER'
        );
    }
}
?>
