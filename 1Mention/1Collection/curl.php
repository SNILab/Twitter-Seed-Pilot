<?php
//Most helpful link ever:
// http://hueniverse.com/2008/10/beginners-guide-to-oauth-part-iv-signing-requests/
//http://www.saiweb.co.uk/mysql/mysql-forcing-utf-8-compliance-for-all-connections

//Constants
const USERNAME = 'PolarSociology';
const PASSWORD = 'bowdoincollege';

const CONSUMER_KEY = '8rGqiXg7uP2fhBpsjy75w';
const CONSUMER_SECRET = 'ruqsmmhQQFolEJSRdcJyZkYUYPuBpE5R0T6QIsU';
const OAUTH_TOKEN = '177293503-FXvTFHxojNSGsWBrLR1h2ug30wlBuplMtlk2A3AU';
const OAUTH_TOKEN_SECRET = 'kp6VYlJwNN5kPpj5511wqiP7SFtt1xS2CIfUxVfY';

const MY_SQL_SERVER = 'localhost';
const MY_SQL_USER = 'root';
const MY_SQL_PWD = 'root';

//Twitter time (roughly)
date_default_timezone_set('UTC');


////////////////////////////////////////////////////////////////////////////////
$url = 'http://api.twitter.com/1/users/lookup.json';
$param = array('user_id' => '131096720,187750522');
$httpMethod = 'POST';
$auth = true;

$a = callAPI($url, $param, $httpMethod, $auth);
print_r($a);


///////////////////////////////////////////////////////////////////////////////////



//Returns a URL constructed from a $baseURL and array of parameters
function URL($baseURL, $params){
	$query = http_build_query($params);
	return $baseURL.'?'.$query;
}



//Returns an OAuth signature (not url encoded) given a URL and parameter array
function signature($baseURL, $params, $httpMethod = 'GET'){
	$sig_key = urlencode(CONSUMER_SECRET).'&'.urlencode(OAUTH_TOKEN_SECRET);
	$signArray = array('oauth_consumer_key' => CONSUMER_KEY,
					'oauth_nonce' => md5(posix_getpid().microtime().mt_rand()),
					'oauth_signature_method' => 'HMAC-SHA1',				
					'oauth_timestamp' => time(),
					'oauth_token' => OAUTH_TOKEN,
					'oauth_version' => '1.0');
	//Normalize merged array of OAuth and user specified parameters
	$allParams = array_merge($signArray, $params);
	ksort($allParams);
	
	$query = http_build_query($allParams);
	$basestring = $httpMethod.'&'.urlencode($baseURL).'&'.urlencode($query);
	$signature = base64_encode(hash_hmac('sha1', $basestring, $sig_key, true));
	return $signature;
}



//Returns an OAuth header given a URL and parameter array
function authHead($baseURL, $params, $httpMethod = 'GET'){
	$sig_key = urlencode(CONSUMER_SECRET).'&'.urlencode(OAUTH_TOKEN_SECRET);
	$signArray = array('oauth_consumer_key' => CONSUMER_KEY,
					'oauth_nonce' => md5(posix_getpid().microtime().mt_rand()),
					'oauth_signature_method' => 'HMAC-SHA1',				
					'oauth_timestamp' => time(),
					'oauth_token' => OAUTH_TOKEN,
					'oauth_version' => '1.0');
	//Normalize merged array of OAuth and user specified parameters
	$allParams = array_merge($signArray, $params);
	ksort($allParams);
	
	$query = http_build_query($allParams);
	$basestring = $httpMethod.'&'.urlencode($baseURL).'&'.urlencode($query);
	$signature = base64_encode(hash_hmac('sha1', $basestring, $sig_key, true));
	$signature = urlencode($signature);

	$authHead = array('Authorization: OAuth
    	oauth_consumer_key="'.$signArray['oauth_consumer_key'].'",
    	oauth_token="'.$signArray['oauth_token'].'",
    	oauth_nonce="'.$signArray['oauth_nonce'].'",
    	oauth_timestamp="'.$signArray['oauth_timestamp'].'",
    	oauth_signature_method="'.$signArray['oauth_signature_method'].'",
    	oauth_version="'.$signArray['oauth_version'].'",
    	oauth_signature="'.$signature.'"','Expect:');
		
	 
	return $authHead;
}




//Makes call to specified $callURL with the specified $httpMethod 
function callAPI($baseURL, $params, $httpMethod = 'GET', $auth = false){
$ch = curl_init(); 
	
if($httpMethod == 'GET'){
	$url = URL($baseURL, $params);
}

else if($httpMethod == 'POST'){
	$url = $baseURL;
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	$params = array();
}

else
	exit('Invalid Http Method: Must be GET or POST');

if($auth){
	$authHead = authHead($baseURL, $params, $httpMethod);	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $authHead);
}


curl_setopt($ch, CURLOPT_URL, $url);


curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$response = curl_exec($ch);
$output = json_decode($response, true);
curl_close($ch);
return $output; 
}





?>