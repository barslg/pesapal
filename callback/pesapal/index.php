<?php

// *************************************************************************
// *                                                                       *
// * WHMCS PesaPal payment Gateway                                         *
// * Copyright (c) WHMCS Ltd. All Rights Reserved,                         *
// * Tested on WHMCS Version: 8.6.1                                      *
// * Release Date: 9th May 2018                                             *
// * V1.4.2                                                                     *
// *************************************************************************
// *                                                                       *
// * Author:  Lazaro Ong'ele | PesaPal Dev Team                            *
// * Email:   developer@pesapal.com                                        *
// * Website: http://developer.pesapal.com | http://www.pesapal.com        *
// *                                                                       *
// *************************************************************************

if (isset($_GET['demo'])){
exit('working');   
}
global $CONFIG;
/*
  include("../../../dbconnect.php");
  include("../../../includes/gatewayfunctions.php"); */

require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../../includes/invoicefunctions.php';

$gatewaymodule = "pesapal";
$gateway = getGatewayVariables($gatewaymodule);
$systemurl = ($CONFIG['SystemSSLURL']) ? $CONFIG['SystemSSLURL'] . '/' : $CONFIG['SystemURL'] . '/';


$baseURL = $gateway['basedomainurl'] ? $gateway['basedomainurl'] : $systemurl;


# Checks gateway module is active before accepting callback
if (!$gateway["type"])
    die("PesaPal Module Not Activated");

$ca = new WHMCS_ClientArea();
$ca->initPage();
$ca->requireLogin();
$data['invoiceid'] = $_GET['OrderMerchantReference'];
$data['transactionid'] = $_GET['OrderTrackingId'];
$data = serialize($data);
$data = base64_encode($data);

$returnURL = $baseURL . 'modules/gateways/pesapal/return.php?pid=' . $data;

header("Location: $returnURL");
exit;
?>