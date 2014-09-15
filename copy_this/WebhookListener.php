<?php
require_once('ext/modules/payment/paymill/WebHooks.php');
require_once('includes/application_top.php');

try{
    if($_GET['type'] == 'CC'){
        $privateKey = trim(MODULE_PAYMENT_PAYMILL_CC_PRIVATEKEY);
    } elseif($_GET['type'] == 'ELV') {
        $privateKey = trim(MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY);
    } else {
        throw new Exception('Invalid Type');
    }

    $controller = new WebHooks($privateKey);

    $body = file_get_contents('php://input');

    $event_json = json_decode($body, true);
    if(isset($event_json['event']['event_type']) && $event_json['event']['event_type'] != ''){
        $controller->setEventParameters(array_merge($_GET, $_POST, $event_json['event']));
    } else {
        WebHooks::log("Invalid Notification", var_export($body, true));
        throw new Exception("Invalid Notification");
    }

} catch (Exception $exception) {
    die($exception->getMessage());
}