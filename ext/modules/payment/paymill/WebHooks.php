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
            xtc_href_link('../WebhookListener.php', '&action=chargeback&type='.$this->_request['type'], 'SSL', false, false) => 'chargeback.executed',
            xtc_href_link('../WebhookListener.php', '&action=refund&type='.$this->_request['type'], 'SSL', false, false) => 'refund.succeeded'
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
     * @param String $type
     * @param String $created_at
     *
     * @throws Exception
     * @return void
     */
    function saveWebhook($id, $url, $mode, $type, $created_at)
    {
        $sql = "REPLACE INTO `pi_paymill_webhooks` (`id`, `url`, `mode`, `type`, `created_at`) VALUES('".$id."','".$url."','".$mode."','".$type."','".$created_at."')";
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
     * @param String $type
     *
     * @return array
     */
    function loadAllWebHooks($type)
    {
        $sql = "SELECT `id` FROM `pi_paymill_webhooks` WHERE `type` = '".$type."'";
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
    static function log($messageInfo, $debugInfo)
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
        $description = $this->_request['event_resource']['transaction']['description'];
        $eventType = $this->_request['action'];
        try{
            $orderId = $this->getOrderIdFromDescription($description);
            $orderStatus = $this->getOrderStatusId($eventType);
            if (isset($orderStatus) && isset($orderId)) {
                xtc_db_query("UPDATE " . TABLE_ORDERS . " SET orders_status='" . $orderStatus . "' WHERE orders_id='" . $orderId . "'");
            }

            $this->successAction();
        } catch (Exception $exception){
            $this->log("An error occurred during Order Update", $this->_request);
        }
    }

    /**
     * @param $statusName
     *
     * @return mixed
     */
    function getOrderStatusId($statusName)
    {
        try{
            $statusArray = xtc_db_fetch_array(xtc_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Paymill [$statusName]' limit 1"));
            $status_id = $statusArray['orders_status_id'];

            if(empty($status_id) || $status_id == ''){
                $status_id = 1;
            }

            return $status_id;
        } catch (Exception $exception) {
            $this->log("Exception in getting Paymill status $statusName", var_export($exception, true));
        }
    }

    /**
     * Returns the state of the webhook option
     * @param $type
     * @return boolean
     */
    function getWebhookState($type)
    {
        return ((constant('MODULE_PAYMENT_PAYMILL_'.$type.'_WEBHOOKS') == 'True') ? true : false);
    }
}