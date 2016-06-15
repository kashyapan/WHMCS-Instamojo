<?php

function trans_status($apikey,$apipritok,$salt,$baseURL){
	require "instamojo_class.php";

		$data = $_POST;
		$mac_provided = $data['mac'];  // Get the MAC from the POST data
		unset($data['mac']);  // Remove the MAC key from the data.
		$ver = explode('.', phpversion());
		$major = (int) $ver[0];
		$minor = (int) $ver[1];
		if($major >= 5 and $minor >= 4){
			 ksort($data, SORT_STRING | SORT_FLAG_CASE);
		}
		else{
			 uksort($data, 'strcasecmp');
		}
		
		$mac_calculated = hash_hmac("sha1", implode("|", $data), $salt);
		
		if($mac_provided == $mac_calculated){
	
			if (strpos($baseURL, 'test') !== false) {
				$api = new Instamojo\Instamojo($apikey, $apipritok , 'https://test.instamojo.com/api/1.1/');

			}else{
				$api = new Instamojo\Instamojo($apikey, $apipritok );
			}

		try {
			$response = $api->paymentRequestStatus($_POST['payment_request_id']);
			$finCr = $response['status'] ;
		}
		catch (Exception $e) {
			print('Error: ' . $e->getMessage());
		}

			if($finCr == "Completed"){
				return true ;
			} else {
				return false ;
			}
	}else{
			echo "MAC mismatch";
	}
}

# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "instamojo";

$GATEWAY = getGatewayVariables($gatewaymodule);

# Checks gateway module is active before accepting callback
if (!$GATEWAY["type"])
    die("Module Not Activated");

$apikey = $GATEWAY["INSTA_API_KEY"];
$apipritok = $GATEWAY["INSTA_PRI_TOK"];
$salt = $GATEWAY["INSTA_PRI_SALT"];
$baseURL = $GATEWAY["INSTA_BASE_URL"];
    
# Get Returned Variables
$merchant_order_id = $_POST["purpose"];
$payment_id = $_POST["payment_id"];

# Checks invoice ID is a valid invoice number or ends processing
$merchant_order_id = checkCbInvoiceID($merchant_order_id, $GATEWAY["name"]); 

# Checks transaction number isn't already in the database and ends processing if it does
checkCbTransID($razorpay_payment_id); 

# Fetch invoice to get the amount
$result = mysql_fetch_assoc(select_query('tblinvoices','total',array("id"=>$merchant_order_id))); 
$amount = $result['total'];

# Check if amount is INR, convert if not.
$currency = getCurrency();
if($currency['code'] !== 'INR') {
    $result = mysql_fetch_array(select_query( "tblcurrencies", "id", array( "code" => 'INR' )));
    $inr_id= $result['id'];
    $converted_amount = convertCurrency($amount,$currency['id'], $inr_id);
}
else {
    $converted_amount = $amount;
}

# Amount in Paisa
$converted_amount = 100*$converted_amount;

$success = true;
$error = "";

$success = $_POST['status']; 
//trans_status funtion checks if there is real credit in account both test and live accounts will work

if (trans_status($apikey,$apipritok,$salt,$baseURL)) {
    # Successful 
    # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
    addInvoicePayment($merchant_order_id, $payment_id, $amount, 0, $GATEWAY["name"]);
    logTransaction($GATEWAY["name"], $_POST, "Successful"); # Save to Gateway Log: name, data array, status
} 
else {
    # Unsuccessful
    # Save to Gateway Log: name, data array, status
    logTransaction($GATEWAY["name"], $_POST, "Unsuccessful-".$error . ". Please check razorpay dashboard for Payment id: ".$_POST['razorpay_payment_id']);
}

?>
