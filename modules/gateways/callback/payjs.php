<?php
use WHMCS\Database\Capsule;

require __DIR__ . '/../../../init.php';
require __DIR__ . '/../class/PayJS.php';
require __DIR__ . '/../../../includes/functions.php';
require __DIR__ . '/../../../includes/gatewayfunctions.php';
require __DIR__ . '/../../../includes/invoicefunctions.php';

$module_data = getGatewayVariables('payjs');

if (!$module_data["type"]) exit(json_encode(['error' => true, 'message' => 'gateway not activated']));

$a = new PayJS();
exit($a->checkCallback($_POST, $module_data));