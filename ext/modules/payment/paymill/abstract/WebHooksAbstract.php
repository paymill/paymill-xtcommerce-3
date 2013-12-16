<?php
require_once('lib/Services/Paymill/Webhooks.php');
abstract class WebHooksAbstract
{
    var $_apiUrl = 'https://api.paymill.com/v2/';
    var $_request = null;

    /** @var  String */
    var $_privateKey = null;

    public function __construct($privateKey)
    {
        $this->_privateKey = $privateKey;
        if(!$this->_validatePrivateKey()){
            throw new Exception("Invalid Private Key.");
        }
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
            throw new Exception($action.' not defined!');
        }
    }

    /**
     * Creates the web-hooks for the status update
     */
    public function registerAction()
    {
        $webHooks = new Services_Paymill_Webhooks($this->_privateKey, $this->_apiUrl);
        $eventList = $this->getEventList();
        foreach($eventList as $url => $eventName){
            $parameters = array(
                'url' => $url,
                'event_types' => array($eventName)
            );
            $webHooks->create($parameters);
        }

    }

    /**
     * Validates the required parameters
     * @return bool
     */
    private function _validatePrivateKey()
    {
        $privateKeyValid = false;
        $privateKey = $this->_privateKey;

        if(isset($privateKey) && $privateKey != '' && $privateKey != '0'){
            $privateKeyValid = true;
        }

       return $privateKeyValid;
    }

    /**
     * Returns the status indicating a successful update
     */
    public function successAction(){
        header("HTTP/1.1 200 OK");
    }

    /**
     * Eventhandler vor refund actions
     */
    public function refundAction(){
        $this->_updateOrder('Refund');
    }

    /**
     * Handles the refund and chargeback events
     * @param $eventType
     */
    private function _updateOrder($eventType){
        $this->log("Updating Order.", var_export($this->_request, true));
//        $data = json_decode();
//        if (!is_null($data) && isset($data->event) && isset($data->event->event_resource)) {
//            if (isset($data->event->event_resource->transaction)) {
//                $description = array();
//                if (preg_match("/OrderID: (\S*)/", $data->event->event_resource->transaction->description, $description)) {
//                    $order = oxNew("oxorder");
//                    $order->load($description[1]);
//                    $status = '';
//                    if ($data->event->event_resource->amount == $order->getTotalOrderSum()) {
//                        $order->oxorder__oxstorno = oxNew('oxField', 1, oxField::T_RAW);
//                        $status = strtoupper($data->event->event_resource->status);
//                    } else {
//                        $status = 'PARTIAL - ' . strtoupper($data->event->event_resource->status);
//                    }
//
//                    $order->oxorder__oxtransstatus = oxNew('oxField', $status, oxField::T_RAW);
//
//                    $order->save();
//                }
//            }
//        }
    }

    public function chargebackAction(){
        //@todo handle chargeback status updates here
    }

    /**
     * @todo Remove this method since it is restricted to xtc
     * @param String $messageInfo
     * @param String $debugInfo
     */
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

    /**
     * Returns the list of events to be created
     * @return array
     */
    abstract function getEventList();
}