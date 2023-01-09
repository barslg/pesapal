<?php
// *************************************************************************
// *                                                                       *
// * WHMCS PesaPal payment Gateway                                         *
// * Copyright (c) WHMCS Ltd. All Rights Reserved,                         *
// * Tested on WHMCS Version: 8.6.1                                       *
// * Release Date: 9th May 2018                                             *
// * V1.4.2                                                                     *
// *************************************************************************
// *                                                                       *
// * Author:  Lazaro Ong'ele | PesaPal Dev Team                            *
// * Email:   developer@pesapal.com                                        *
// * Website: http://developer.pesapal.com | http://www.pesapal.com        *
// *                                                                       *
// *************************************************************************

use WHMCS\Database\Capsule;
require('pesapalV3Helper.php'); 
define("CLIENTAREA",true);
//define("FORCESSL",true); // Uncomment to force the page to use https://
require("../../../init.php");
include("../../../includes/gatewayfunctions.php");
include("../../../invoicefunctions.php");
    
global $CONFIG;
    
$gatewaymodule  	= "pesapal"; 
$gateway        	= getGatewayVariables($gatewaymodule);
$systemurl 		= ($CONFIG['SystemSSLURL']) ? $CONFIG['SystemSSLURL'].'/' : $CONFIG['SystemURL'].'/';

$baseURL = $gateway['basedomainurl'] ? $gateway['basedomainurl'] : $systemurl;

    
# Checks gateway module is active before accepting callback
if (!$gateway["type"]) die("PesaPal Module Not Activated");
    
$order          = base64_decode($_POST["order"]);

$orderDetails   = array();
$orderDetails   = unserialize($order);
    
$invoiceid= checkCbInvoiceID($orderDetails['invoiceid'],$gateway["name"]);
    
if (!$invoiceid) die("Invalid order used");

$isDemoMode = $gateway['testmode'];
$apimode = ( $isDemoMode ) ? "demo" : "live";

$pesapalV3Helper = new pesapalV3Helper($apimode);
$access_token = $pesapalV3Helper->getAccessToken(trim($gateway['consumerkey']), trim($gateway['consumersecret']));

if($access_token) {
    $root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
    $ipnurl = $root.'modules/gateways/pesapal/ipn.php';
	$ipn_id = $pesapalV3Helper->generateNotificationId($ipnurl, $access_token);
	
    // 	echo $ipnurl;
	//insert a ipn id
	try {
	    //check if value exist before insert
		$data = Capsule::table('tblpaymentgateways')
			->where("setting", "=", "notificationid")
			->first();
		if($data->gateway != "pesapal"){

	       // print_r($data);
	        
	       // echo '<br/> hallo';
	        
    		$insert_array = [
    			"gateway" => "pesapal",
    			"setting" => "notificationid",
    			"value" => $ipn_id,
    			"order" => 0,
    		];
    		Capsule::table('tblpaymentgateways')
    			->insert($insert_array);
		 }else if($data->gateway == "pesapal" && $data->value != $ipn_id){
		    $update_data =  ['value' => $ipn_id];
            Capsule::table('tblpaymentgateways')->where('setting', '=', 'notificationid')
       ->update($update_data);
		 }
	} catch(\Illuminate\Database\QueryException $ex){
		echo $ex->getMessage();
	} catch (Exception $e) {
		echo $e->getMessage();
	}
	
	$request = new stdClass();
	$request->currency = $orderDetails['currency'];
	$amount = $orderDetails['amount'];
	$amount = number_format($amount, 2);//format amount to 2 decimal places
	$request->amount = $amount;
	// $request->pesapalMerchantReference = $orderDetails['invoiceid'];
	$request->pesapalMerchantReference = ($gateway['orderprefix']) ? strtoupper(str_replace(" ","",$gateway['orderprefix']))."-".$orderDetails['invoiceid'] : $orderDetails['invoiceid'];
	$request->pesapalDescription = $orderDetails['description'];
	$request->billing_phone = $orderDetails['clientdetails']['phonenumber'];
	$request->billing_email = $orderDetails['clientdetails']['email'];
	$request->billing_country = $orderDetails['clientdetails']['countrycode'];;
	$request->billing_first_name = $orderDetails['clientdetails']['firstname'];
	$request->billing_middle_name = '';
	$request->billing_last_name = $orderDetails['clientdetails']['lastname'];
	$request->billing_address_1 = $orderDetails['clientdetails']['address1'];
	$request->billing_address_2 = $orderDetails['clientdetails']['address2'];
	$request->billing_city = $orderDetails['clientdetails']['city'];
	$request->billing_state = $orderDetails['clientdetails']['state'];
	$request->billing_postcode = $orderDetails['clientdetails']['postcode'];
	$request->billing_zipcode = '';
	$request->callback_url = $baseURL.'modules/gateways/callback/pesapal';
	$request->notification_id = !$gateway['notificationid'] || $gateway['notificationid'] != $ipn_id  ? $ipn_id : $gateway['notificationid'];
	$request->terms_and_conditions_id = '';

	$order_response = $pesapalV3Helper->getMerchertOrderURL($request, $access_token);

	if($order_response->status == 200){
		$iframe_src = $order_response->redirect_url;

		$ca = new WHMCS_ClientArea();
		$ca->setPageTitle("Secure Online Payments | PesaPal");
		$ca->addToBreadCrumb('index.php',$orderDetails['globalsystemname']);
		$ca->initPage();
		$ca->assign('iframe_src', $iframe_src);
		
		$ca->setTemplate('pesapal_iframe'); 
		
		$ca->output();
	}
}

function logError($data = NULL, $logFile = 'frame') {

    $output = print_r($data, TRUE);

    $logPath = __DIR__ . '/pesapal/' . $logFile . '.log';

    if (!file_exists($logPath)) {
        fopen($logPath, "w") or die('Cannot open file:  ' . $logPath);
    }

    error_log(date('m/d/Y H:i:s', time()) . "----- " . $output . "\n", 3, $logPath);
}

?>