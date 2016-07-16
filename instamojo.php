<?php

function instamojo_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"Instamojo"),
     "INSTA_API_KEY" => array("FriendlyName" => "Private Api Key", "Type" => "text", "Size" => "32", "Description" => "Private API Key provided by Instamojo", ),
     "INSTA_PRI_TOK" => array("FriendlyName" => "Private Auth Token", "Type" => "text", "Size" => "32", "Description" => "Private Auth Token provided by Instamojo", ),
     "INSTA_PRI_SALT" => array("FriendlyName" => "Salt", "Type" => "text", "Size" => "32", "Description" => "Salt provided by Instamojo", ),
     "INSTA_BASE_URL" => array("FriendlyName" => "Instamojo Base URL", "Type" => "textarea", "Rows" => "1", "Description" => "https://www.instamojo.com/api/1.1/payment-requests/ for LIVE mode", ),
     "surl" => array("FriendlyName" => "Redirect URL", "Type" => "textarea", "Rows" => "1", "Description" => "Please Enter url where you need to redirect after completing transaction", ),
 	 );

	return $configarray;
}

function instamojo_link($params) {
	
    // Gateway Configuration Parameters
    $INSTA_API_KEY = $params['INSTA_API_KEY'];
    $INSTA_PRI_TOK = $params['INSTA_PRI_TOK'];
    $INSTA_PRI_SALT = $params['INSTA_PRI_SALT'];
    $INSTA_WH    = $params['systemurl'].'/modules/gateways/callback/instamojo.php';
    $INSTA_BASE_URL = $params['INSTA_BASE_URL'];
    $surl = $params['surl'];
	
	
	# Invoice Variables
	$invoiceid = $params['invoiceid'];   	
	$productinfo = $params['description'];	
	$amount = $params['amount']; # Format: ##.##
	$currency = $params['currency']; # Currency Code

	
	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	# System Variables
	$companyname = $params['companyname'];
	$systemurl = $params['systemurl'];
	$currency = $params['currency'];
	
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $INSTA_BASE_URL );
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER,
            array("X-Api-Key:$INSTA_API_KEY",
                  "X-Auth-Token:$INSTA_PRI_TOK"));
$payload = Array(
    'purpose' => $invoiceid,
    'amount' => $amount,  
    'phone' => $phone , 
    'buyer_name' => $firstname ,
    'redirect_url' => $surl,
    'send_email' => false,
    'webhook' => $INSTA_WH ,
    'send_sms' => false,
    'email' => $email ,
    'allow_repeated_payments' => false
);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
$response = curl_exec($ch);
curl_close($ch); 

$d = json_decode($response , true) ;

$action =  $d['payment_request']['longurl'] ;
//$action =  'This' ;


	$code = '
	<script>
function paynow(){
	window.location="'.$action.'";
	
}
	</script>
	
	<button class="btn btn-success" onclick="paynow()">Proceed </button>
      ';
	  
	  return $code;
	
	
}

?>
