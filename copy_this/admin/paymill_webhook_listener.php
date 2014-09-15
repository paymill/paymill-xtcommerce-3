<?php
require_once('includes/application_top.php');
require_once('../ext/modules/payment/paymill/WebHooks.php');

try{
    if($_GET['type'] == 'CC'){
        $privateKey = trim(MODULE_PAYMENT_PAYMILL_CC_PRIVATEKEY);
    } elseif($_GET['type'] == 'ELV') {
        $privateKey = trim(MODULE_PAYMENT_PAYMILL_ELV_PRIVATEKEY);
    } else {
        throw new Exception('Invalid Type');
    }

    $controller = new WebHooks($privateKey);
    $controller->setEventParameters(array_merge($_GET, $_POST));

} catch (Exception $exception) {
    die($exception->getMessage());
}