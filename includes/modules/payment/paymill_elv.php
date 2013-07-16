<?php

require_once('paymill/abstract/paymill.php');

class paymill_elv extends paymill
{

    function paymill_elv()
    {
        $this->code = 'paymill_elv';
        $this->version = '1.0.6';
        $this->title = MODULE_PAYMENT_PAYMILL_ELV_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_PAYMILL_ELV_TEXT_PUBLIC_TITLE;
        $this->sort_order = MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PAYMILL_ELV_STATUS == 'True') ? true : false);
        $this->privateKey = trim(MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY);
        $this->tmpOrders = true;
        $this->tmpStatus = MODULE_PAYMENT_PAYMILL_ELV_TMP_STATUS_ID;
        $this->order_status = MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID;
        $this->form_action_url = '';
        $this->logging = ((MODULE_PAYMENT_PAYMILL_ELV_LOGGING == 'True') ? true : false);;
    }

    function selection()
    {
        $resourcesDir = HTTPS_SERVER . DIR_WS_CATALOG . '/includes/modules/payment/paymill/resources/';

        $formArray = array();

        $formArray[] = array(
            'title' => '',
            'field' => '<link rel="stylesheet" type="text/css" href="' . HTTPS_SERVER . DIR_WS_CATALOG . 'css/paymill.css"/>'
        );

        $formArray[] =  array(
            'title' => '',
            'field' => xtc_image($resourcesDir . 'icon_elv.png')
        );

        $formArray[] = array(
            'title' => MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT,
            'field' => '<br/><input type="text" id="account-number" class="form-row-paymill"/>'
        );

        $formArray[] = array(
            'title' => MODULE_PAYMENT_PAYMILL_ELV_TEXT_BANKCODE,
            'field' => '<br/><input type="text" id="bank-code" class="form-row-paymill"/>'
        );

        $formArray[] = array(
            'title' => MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_HOLDER,
            'field' => '<br/><input type="text" id="bank-owner" class="form-row-paymill"/>'
        );

        $formArray[] = array(
        'field' =>
            '<div class="form-row">'
              . '<div class="paymill_powered">'
                   . '<div class="paymill_credits">'
                       . MODULE_PAYMENT_PAYMILL_ELV_TEXT_SAVED
                      . ' <a href="http://www.paymill.de" target="_blank">PAYMILL</a>'
                   . '</div>'
               . '</div>'
           . '</div>'
        );

        $script = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>'
                . '<script type="text/javascript">'
                    . 'var PAYMILL_PUBLIC_KEY = "' . trim(MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY) . '";'
                . '</script>'
                . '<script type="text/javascript" src="' . $this->bridgeUrl . '"></script>'
                . '<script type="text/javascript">'
                    . 'var elvlogging = "' . MODULE_PAYMENT_PAYMILL_ELV_LOGGING . '";'
                    . 'var elv_account_number_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_INVALID) . '";'
                    . 'var elv_bank_code_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_BANKCODE_INVALID) . '";'
                    . 'var elv_bank_owner_invalid = "' . utf8_decode(MODULE_PAYMENT_PAYMILL_ELV_TEXT_ACCOUNT_HOLDER_INVALID) . '";'
                    . file_get_contents(DIR_FS_CATALOG . 'javascript/paymill_elv_checkout.js')
                . '</script>';

        $formArray[] = array(
            'title' => "",
            'field' => $script
        );

        $selection = array(
            'id' => $this->code,
            'module' => $this->title,
            'fields' => $formArray,
            'description' => $this->info
        );

        return $selection;
    }
    
    function pre_confirmation_check()
    {
        parent::pre_confirmation_check();
        
        unset($_SESSION['paymill_authorized_amount']);
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
        if (xtc_db_num_rows(xtc_db_query("SELECT * from " . TABLE_ORDERS_STATUS . " where orders_status_name LIKE 'Pending Payment (Paymill)'")) == 0) {
            //based on orders_status.php with action save new orders_status_id
            $next_id_query = xtc_db_query("select max(orders_status_id) as orders_status_id from " . TABLE_ORDERS_STATUS . "");
            $next_id = xtc_db_fetch_array($next_id_query);
            $tmp_status_id = $next_id['orders_status_id'] + 1;
            //based on orders_status.php ends
            xtc_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES (" . $tmp_status_id . ",1, 'Pending Payment (Paymill)'),(" . $tmp_status_id . ",2,'Ausstehende Zahlung (Paymill)');");
        } else {
            $tmp_status_query = xtc_db_query("SELECT * from " . TABLE_ORDERS_STATUS . " where orders_status_name LIKE 'Pending Payment (Paymill)'");
            $tmp_status = xtc_db_fetch_array($tmp_status_query);
            $tmp_status_id = $tmp_status['orders_status_id'];
        }
        
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_STATUS', 'True', '6', '1', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_ALLOWED', '', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER', '0', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY', '', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY', '', '6', '0', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID', '0',  '6', '0', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now())");
	xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_PAYMILL_ELV_TMP_STATUS_ID', '" . $tmp_status_id . "',  '6', '8', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PAYMENT_PAYMILL_ELV_LOGGING', 'False', '6', '0', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");    
    }

    function remove()
    {
        xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");
    }

    function keys()
    {
        return array(
            'MODULE_PAYMENT_PAYMILL_ELV_STATUS',
            'MODULE_PAYMENT_PAYMILL_ELV_LOGGING',
            'MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY',
            'MODULE_PAYMENT_PAYMILL_ELV_PUBLICKEY',
            'MODULE_PAYMENT_PAYMILL_ELV_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYMILL_ELV_TMP_STATUS_ID',
            'MODULE_PAYMENT_PAYMILL_ELV_SORT_ORDER',
            'MODULE_PAYMENT_PAYMILL_ELV_ALLOWED'
        );
    }

}
