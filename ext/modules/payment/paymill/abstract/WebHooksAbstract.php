<?php
abstract class WebHooksAbstract
{
    var $_apiUrl = 'https://api.paymill.com/v2/';

    var $_request = null;

    /** @var  String */
    var $_privateKey = null;

    public function __construct($privateKey)
    {
        $this->_privateKey = $privateKey;
        if (!$this->_validatePrivateKey()) {
            throw new Exception("Invalid Private Key.");
        }
    }

    /**
     * Validates the required parameters
     *
     * @return bool
     */
    private function _validatePrivateKey()
    {
        $privateKeyValid = false;
        $privateKey = $this->_privateKey;

        if (isset($privateKey) && $privateKey != '' && $privateKey != '0') {
            $privateKeyValid = true;
        }

        return $privateKeyValid;
    }

    /**
     * Sets the parameters for event handling
     *
     * @param $request
     *
     * @throws Exception
     */
    public function setEventParameters($request)
    {
        $this->_request = $request;

        if (!array_key_exists('action', $this->_request)) {
            throw new Exception('Action not defined!');
        }

        $action = $this->_request['action'] . 'Action';

        if (method_exists($this, $action)) {
            $this->$action();
        } else {
            throw new Exception($action . ' not defined!');
        }
    }

    /**
     * Creates the web-hooks for the status update
     */
    public function registerAction()
    {
        $this->requireWebhooks();
        $webHooks = new Services_Paymill_Webhooks($this->_privateKey, $this->_apiUrl);
        $eventList = $this->getEventList();
        $data = array();
        foreach ($eventList as $url => $eventName) {
            $parameters = array(
                'url'         => $url,
                'event_types' => array($eventName)
            );
            $hook = $webHooks->create($parameters);
            $this->saveWebhook($hook['id'],$hook['url'], $hook['livemode']? 'live' : 'test', $this->_request['type'], $hook['created_at']);
            $data[] = $hook;
        }
    }

    /**
     * Removes all registered web-hooks
     */
    public function removeAction()
    {
        $this->requireWebhooks();
        $type = $this->_request['type'];
        $webHooks = new Services_Paymill_Webhooks($this->_privateKey, $this->_apiUrl);
        $hooks = $this->loadAllWebHooks($type);

        foreach ($hooks as $hook) {
            $webHooks->delete($hook);
            $this->removeWebhook($hook);
        }
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
     * @return void
     */
    abstract function saveWebhook($id, $url, $mode, $type, $created_at);

    /**
     * Removes the web-hook from the web-hook table
     * @param String $id
     * @throws Exception
     * @return array
     */
    abstract function removeWebhook($id);

    /**
     * Returns the ids of all web-hooks from the web-hook table
     *
     * @param String $type
     *
     * @return array
     */
    abstract function loadAllWebHooks($type);

    /**
     * Required the Libs WebHooks class
     * @return void
     */
    abstract function requireWebhooks();

    /**
     * Required the Libs WebHooks class
     * @return void
     */
    abstract function requireTransactions();

    /**
     * Required the Libs WebHooks class
     * @return void
     */
    abstract function requireRefunds();

    /**
     * Returns the list of events to be created
     *
     * @return array
     */
    abstract function getEventList();

    /**
     * Returns the status indicating a successful update
     */
    public function successAction()
    {
        exit(header("HTTP/1.1 200 OK"));
    }

    /**
     * Eventhandler for refund actions
     */
    public function refundAction()
    {
        $type = $this->_request['type'];
        $refundId = $this->_request['event_resource']['id'];
        $this->requireRefunds();
        $refunds = new Services_Paymill_Refunds($this->_privateKey, $this->_apiUrl);
        $refund = $refunds->getOne($refundId);
        if($this->getWebhookState($type) && isset($refund['id'])){
            $this->_request['action'] = 'Refund';
            $this->updateOrderStatus();
        } else {
            $this->successAction();
        }
    }

    /**
     * Eventhandler for chargeback actions
     */
    public function chargebackAction()
    {
        $type = $this->_request['type'];
        $transactionId = $this->_request['event_resource']['id'];
        $this->requireTransactions();
        $transactions = new Services_Paymill_Transactions($this->_privateKey, $this->_apiUrl);
        $transaction = $transactions->getOne($transactionId);
        if($this->getWebhookState($type) && isset($transaction['id'])){
            $this->_request['action'] = 'Chargeback';
            $this->updateOrderStatus();
        } else {

            $this->successAction();
        }
    }

    /**
     * Returns the orderId obtained from the description
     * @param $description
     * @return null
     */
    public function getOrderIdFromDescription($description)
    {
        if (preg_match("/OrderID: (\S*)/", $description, $description)) {
            $orderId = $description[1];
            if(isset($orderId) && $orderId != ''){
                return $orderId;
            }
        }
        return null;
    }

    /**
     * Returns the state of the webhook option
     * @param $type
     * @return boolean
     */
    abstract function getWebhookState($type);

    /**
     * Changes the Status of the current order (based on the notification)
     * @return void
     */
    abstract function updateOrderStatus();
}