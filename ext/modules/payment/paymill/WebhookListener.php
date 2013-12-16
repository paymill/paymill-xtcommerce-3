<?php
require_once('WebHooks.php');
require_once('../../../../xtc_toolbox.php');
$toolbox = new xtc_toolbox();
$controller = new WebHooks($toolbox);
try{
    $controller->setEventParameters(array_merge($_GET, $_POST));
} catch (Exception $exception) {
    die($exception->getMessage());
}