<?php
require_once('paymill/paymill_abstract.php');

class paymill_elv extends paymill_abstract
{

    function paymill_elv()
    {
        parent::paymill_abstract();
        global $order;

        $this->code = 'paymill_elv';
        $this->version = '1.1.0';
        $this->api_version = '2';
        $this->title = MODULE_PAYMENT_PAYMILL_ELV_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_PAYMILL_ELV_TEXT_PUBLIC_TITLE;

        if (defined('MODULE_PAYMENT_PAYMILL_ELV_STATUS')) {
            $this->enabled = ((MODULE_PAYMENT_PAYMILL_ELV_STATUS == 'True') ? true : false);
            $this->sort_order = MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER;
            $this->privateKey = trim(MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY);
            $this->logging = ((MODULE_PAYMENT_PAYMILL_ELV_LOGGING == 'True') ? true : false);
            $this->publicKey = MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY;
            $this->fastCheckoutFlag = ((MODULE_PAYMENT_PAYMILL_ELV_FASTCHECKOUT == 'True') ? true : false);
            $this->payments = new Services_Paymill_Payments($this->privateKey, $this->apiUrl);
            $this->clients = new Services_Paymill_Clients(trim($this->privateKey), $this->apiUrl);
            if ((int) MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID > 0) {
                $this->order_status = MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID;
            }
        }

        if (is_object($order)) $this->update_status();
    }

    function pre_confirmation_check()
    {
        global $oscTemplate, $order;

        parent::pre_confirmation_check();

        $oscTemplate->addBlock('<script type="text/javascript" src="ext/modules/payment/paymill/public/javascript/elv.js"></script>', 'header_tags');

        $payment = $this->getPayment($_SESSION['customer_id']);
        
        $this->fastCheckout->setFastCheckoutFlag($this->fastCheckoutFlag);
        
        $script = '<script type="text/javascript">'
                . 'var elvlogging = "' . MODULE_PAYMENT_PAYMILL_ELV_LOGGING . '";'
                . 'var elv_account_number_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_INVALID) . '";'
                . 'var elv_bank_code_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_BANKCODE_INVALID) . '";'
                . 'var elv_bank_owner_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_HOLDER_INVALID) . '";'
                . 'var paymill_account_name = ' . json_encode(tep_output_string_protected($order->billing['firstname'] . ' ' . $order->billing['lastname'])) . ';'
                . 'var paymill_elv_code = "' . $payment['code'] . '";'
                . 'var paymill_elv_holder = "' . $payment['holder'] . '";'
                . 'var paymill_elv_account = "' . $payment['account'] . '";'
                . 'var paymill_elv_fastcheckout = ' . $this->fastCheckout->canCustomerFastCheckoutElvTemplate($_SESSION['customer_id']) . ';'
                . '</script>';

        $oscTemplate->addBlock($script, 'header_tags');

        $oscTemplate->addBlock('<form id="paymill_form" action="' . tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL') . '" method="post" style="display: none;"></form>', 'footer_scripts');
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
        $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_HOLDER,
                                                      'field' => '<span id="account-name-field"></span>'),
                                                array('title' => MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT,
                                                      'field' => '<span id="account-number-field"></span>'),
                                                array('title' => MODULE_PAYMENT_PAYMILL_ELV_TEXT_BANKCODE,
                                                      'field' => '<span id="bank-code-field"></span>')));

        return $confirmation;
    }

    function check()
    {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_PAYMILL_ELV_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function install()
    {
        global $language;

        parent::install();
        
        include(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $language . '/modules/payment/paymill_elv.php');

        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('" . MODULE_PAYMENT_PAYMILL_ELV_STATUS_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_STATUS_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_STATUS', 'True', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('" . MODULE_PAYMENT_PAYMILL_ELV_FASTCHECKOUT_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_FASTCHECKOUT_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_FASTCHECKOUT', 'False', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('" . MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER', '0', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('" . MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY', '0', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('" . MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY', '0', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" . MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID', '0',  '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('" . MODULE_PAYMENT_PAYMILL_ELV_LOGGING_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_LOGGING_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_LOGGING', 'False', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" . MODULE_PAYMENT_PAYMILL_ELV_TRANS_ORDER_STATUS_ID_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_TRANS_ORDER_STATUS_ID_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_TRANSACTION_ORDER_STATUS_ID', '" . $this->getOrderStatusTransactionID() . "', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, use_function, set_function, date_added) values ('" . MODULE_PAYMENT_PAYMILL_ELV_ZONE_TITLE . "', '" . MODULE_PAYMENT_PAYMILL_ELV_ZONE_DESC . "', 'MODULE_PAYMENT_PAYMILL_ELV_ZONE', '0', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
    }

    function keys()
    {
        return array(
            'MODULE_PAYMENT_PAYMILL_ELV_STATUS',
            'MODULE_PAYMENT_PAYMILL_ELV_FASTCHECKOUT',
            'MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY',
            'MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY',
            'MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYMILL_ELV_TRANSACTION_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYMILL_ELV_ZONE',
            'MODULE_PAYMENT_PAYMILL_ELV_LOGGING',
            'MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER'
        );
    }
}
?>
