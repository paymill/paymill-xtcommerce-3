<?php
require_once('WebHooks.php');
require_once ('includes/application_top.php');
$privateKey = '';
$controller = new WebHooks($privateKey);
try{
    $controller->setEventParameters(array_merge($_GET, $_POST));
} catch (Exception $exception) {
    die($exception->getMessage());
}