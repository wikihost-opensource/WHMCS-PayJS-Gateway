<?php
use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../../../../init.php';

if (!$_SESSION['uid']){
    exit(json_encode(['error' => true, 'message' => 'need login']));
} elseif (!$_GET['id']){
    exit(json_encode(['error' => true, 'message' => 'need invoiceid']));
}

$invoice_query = Capsule::table('tblinvoices')->where('id', $_GET['id'])->where('paymentmethod', 'payjs');

if (! (clone $invoice_query)->exists()){
    exit(json_encode(['error' => true, 'message' => 'invoice not exists']));
}

$invoice = $invoice_query->first();
exit(json_encode(['error' => false, 'data' => ['paid' => ($invoice->status == 'Paid')]]));