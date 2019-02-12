<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

function payjs_MetaData() {
    return [
        'DisplayName' => 'WikiHost - PayJS模块',
        'APIVersion' => '1.1',
    ];
}

function payjs_config() {
    require_once __DIR__ ."/class/PayJS.php";
    $payjs = new PayJS();
    return $payjs->getConfiguration();
}

function payjs_link($params)
{
    require_once __DIR__ ."/class/PayJS.php";
    $payjs = new PayJS();
    return $payjs->getHtml($params);
}