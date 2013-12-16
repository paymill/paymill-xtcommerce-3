<?php
require_once('abstract/WebHooksAbstract.php');
class WebHooks extends WebHooksAbstract
{
    /**
     * Returns the list of events to be created
     * @return array
     */
    function getEventList(){
        $eventList = array(
            xtc_href_link('../WebhookListener.php', '&action=chargeback', 'SSL', false, false) => 'chargeback.executed',
            xtc_href_link('../WebhookListener.php', '&action=refund', 'SSL', false, false) => 'refund.succeeded'
        );

        return $eventList;
    }

    /**
     * Requires the php libs webhook class
     */
    function requireWebhooks()
    {
        require_once('lib/Services/Paymill/Webhooks.php');
    }

    /**
     * Saves the web-hook into the web-hook table
     *
     * @param String $id
     * @param String $url
     * @param String $mode
     * @param String $created_at
     * @throws Exception
     * @return void
     */
    function saveWebhook($id, $url, $mode, $created_at)
    {
        $sql = "REPLACE INTO `pi_paymill_webhooks` (`id`, `url`, `mode`, `created_at`) VALUES('".$id."','".$url."','".$mode."','".$created_at."')";
        $success = xtc_db_query($sql);
        if(!$success){
            throw new Exception("Webhook data could not be saved.");
        }

    }

    /**
     * Removes the web-hook from the web-hook table
     *
     * @param String $id
     *
     * @throws Exception
     * @return void
     */
    function removeWebhook($id)
    {
        $sql = "DELETE FROM `pi_paymill_webhooks` WHERE `id` = '".$id."'";
        $success = xtc_db_query($sql);
        if(!$success){
            throw new Exception("Webhook data could not be deleted.");
        }
    }

    /**
     * Returns the ids of all web-hooks from the web-hook table
     *
     * @throws Exception
     * @return array
     */
    function loadAllWebHooks()
    {
        $sql = "SELECT `id` FROM `pi_paymill_webhooks`";
        $store = xtc_db_query($sql);
        $result = array();
        while($row = xtc_db_fetch_array($store)){
            $result[] = $row['id'];
        }

        return $result;
    }

    /**
     * Logs parameters into the db without relying on the logging option
     * @param String $messageInfo
     * @param String $debugInfo
     */
    function log($messageInfo, $debugInfo)
    {
        xtc_db_query("INSERT INTO `pi_paymill_logging` "
                     . "(debug, message, identifier) "
                     . "VALUES('"
                     . xtc_db_input($debugInfo) . "', '"
                     . xtc_db_input($messageInfo) . "', '"
                     . xtc_db_input($_SESSION['paymill_identifier'])
                     . "')"
        );
    }

    /**
     * Updates the order state
     */
    function updateOrderStatus()
    {
        $description = $this->_request['event']['event_resource']['description'];
        $eventType = $this->_request['action'];
        $orderId = $this->getOrderIdFromDescription($description);
        $orderStatus = $this->getOrderStatusId($eventType);
        if ($orderStatus) {
            xtc_db_query("UPDATE " . TABLE_ORDERS . " SET orders_status='" . $orderStatus . "' WHERE orders_id='" . $orderId . "'");
        }

        $this->successAction();
    }

    /**
     * @param $statusName
     *
     * @return mixed
     */
    function getOrderStatusId($statusName)
    {
        $check_query = xtc_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Paymill ['.$statusName.']' limit 1");

        if (xtc_db_num_rows($check_query) < 1) {
            $status_query = xtc_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
            $status = xtc_db_fetch_array($status_query);

            $status_id = $status['status_id'] + 1;

            $languages = xtc_get_languages();

            foreach ($languages as $lang) {
                xtc_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $lang['id'] . "', 'Paymill ['.$statusName.']')");
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

    /**
     * Returns the state of the webhook option
     *
     * @return boolean
     */
    function getWebhookState()
    {
       return ((MODULE_PAYMENT_PAYMILL_CC_WEBHOOKS == 'True') ? true : false);
    }
}