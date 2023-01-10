<?php

// *************************************************************************
// *                                                                       *
// * WHMCS PesaPal payment Gateway                                         *
// * Copyright (c) WHMCS Ltd. All Rights Reserved,                         *
// * Tested on WHMCS Version: 8.6.1                                       *
// * Release Date: 9th May 2018                                             *
// * V1.4.2                                                                       *
// *************************************************************************
// *                                                                       *
// * Author:  Lazaro Ong'ele | PesaPal Dev Team                            *
// * Email:   developer@pesapal.com                                        *
// * Website: http://developer.pesapal.com | http://www.pesapal.com        *
// *                                                                       *
// *************************************************************************


//include("../../../dbconnect.php");
require('pesapalV3Helper.php'); 
require_once __DIR__ . '/../../../init.php';
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
// include("checkStatus.php");

use WHMCS\Database\Capsule as DB;

global $CONFIG;
$ca = new WHMCS_ClientArea();
$ca->initPage();
$ca->requireLogin();

$gatewaymodule  	= "pesapal"; 
$gateway        	= getGatewayVariables($gatewaymodule);

if (!$gateway["type"])
    die("PesaPal Module Not Activated");

$isDemoMode = $gateway['testmode'];
$apimode = ( $isDemoMode ) ? "demo" : "live";
$pesapalV3Helper = new pesapalV3Helper($apimode);




$systemurl = ($CONFIG['SystemSSLURL']) ? $CONFIG['SystemSSLURL'] . '/' : $CONFIG['SystemURL'] . '/';

# Checks gateway module is active before accepting callback

$data = $_GET['pid'];

$data = base64_decode($data);

$data = unserialize($data);

$pesapalTrackingId = $data['transactionid'];
$pesapalMerchantReference = $data['invoiceid'];


// $pesapalV3Helper->dbg($data);

$access_token = $pesapalV3Helper->getAccessToken(trim($gateway['consumerkey']), trim($gateway['consumersecret']));

If(!$access_token){
    exit;
}


$response = $pesapalV3Helper->getTransactionStatus($pesapalTrackingId, $access_token);
$status			= strtoupper($response->payment_status_description);

$transid = $pesapalTrackingId;
$amount = NULL;
$fee = NULL;

# Checks invoice ID is a valid invoice number or ends processing
$exploded = explode('-', $pesapalMerchantReference);
$count = count($exploded);

$invoiceId = $count === 1 ? $pesapalMerchantReference : $exploded[1];

$invoiceid = checkCbInvoiceID($invoiceId, $gateway['name']);

# Checks transaction number isn't already in the database and ends processing if it does
//checkCbTransID($transid);


if ($status == "COMPLETED") {

    addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule);
    logTransaction($gateway["name"], $data, "Completed");

    $postData=[
        'paymentmethod' => $gatewaymodule,
    ];

    $res=DB::table('tblinvoices')->where('id',$invoiceid)->update($postData);

    $invoice_url = $systemurl . 'viewinvoice.php?id=' . $invoiceid;
    // header("Location: $invoice_url");
    // exit;
} elseif ($status == "FAILED")
$values["status"] = "Failed";
else
    $values["status"] = "Unpaid";

$command = "UpdateInvoice";
$adminuser = $gateway["adminuser"];
$values["invoiceid"] = $invoiceid;
// $values["paymentmethod"] = $gateway["name"];
$values["paymentmethod"] = $gatewaymodule;

$results = localAPI($command, $values, $adminuser);
logTransaction($gateway["name"], $_GET, $results);

//Redirect to callback page
$ca->setPageTitle("Pesapal | Payment Summary");
$ca->addToBreadCrumb('index.php', 'Payment Summary');
$ca->assign('status', $status);
$ca->assign('invoiceid', $invoiceid);
$ca->assign('pesapalTrackingId', $pesapalTrackingId);
$ca->setTemplate('pesapal_callback');
$ca->output();

?>