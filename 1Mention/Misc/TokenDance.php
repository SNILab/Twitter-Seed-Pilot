<?php
$username = 'skiguru22';
$password = 'clemens165528';

$consumer_key = "W3yT9Obhb9JnM6NiellurA";
$secret = "w24PE0r32QocRbBHgMZ0LmOSW7BhI4TyQdBszyBH138";
$callback = urlencode("http://139.140.68.239:8888");
$reqURL = 'http://api.twitter.com/oauth/request_token';
$authURL = "http://api.twitter.com/oauth/authenticate";
$accessURL = "http://api.twitter.com/oauth/access_token";


//REQUEST TOKEN/////////////////////////////////////////////////////////////////
//Build request parameters
$reqParam = "oauth_consumer_key=".$consumer_key;
$reqParam.= "&oauth_nonce=".md5(posix_getpid().microtime().mt_rand());
$reqParam.= "&oauth_signature_method=HMAC-SHA1";
$reqParam.= "&oauth_timestamp=".time();
$reqParam.= "&oauth_version=1.0";

//Build basestring, signature
$basestring = "GET&".urlencode($reqURL).'&'.urlencode($reqParam);
$signature = base64_encode(hash_hmac('sha1', $basestring, $secret.'&', true)); 
$signature = "&oauth_signature=".urlencode($signature);

//Build URL
$url = $reqURL.'?'.$reqParam.$signature;


//Make call
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

//Creates associative array, defining $oauth_token and $oauth_token_secret
parse_str($response);


//AUTHORIZE TOKEN///////////////////////////////////////////////////////////////
//Build request parameters
$authParam = "oauth_token=".$oauth_token;
$authParam.= "&oauth_callback=".$callback;

//Build URL
$url = $authURL.'?'.$authParam;

//Posts url to top of Twitter sign in screen
echo($url);

//Make call
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo($response);




//EXCHANGE TOKEN///////////////////////////////////////////////////////////////


?>