<?php
//Constants
const USERNAME = 'skiguru22';
const PASSWORD = 'clemens165528';
const CALLBACK = 'http://139.140.68.239:8888';

const CONSUMER_KEY = 'W3yT9Obhb9JnM6NiellurA';
const CONSUMER_SECRET = 'w24PE0r32QocRbBHgMZ0LmOSW7BhI4TyQdBszyBH138';
const OAUTH_TOKEN = '137932781-GrQX9FJAhkVYcG3pt0LbTlBq2QJxaOBQjAjI4YIc';
const OAUTH_TOKEN_SECRET = 'ZsR6orFPVdz1m7EdHsaLyaVcYTBfLCnfUXoHPA6c0';

const MY_SQL_SERVER = 'localhost';
const MY_SQL_USER = 'root';
const MY_SQL_PWD = 'root';

//Twitter time (roughly)
date_default_timezone_set('UTC');

$url = 'http://search.twitter.com/search.json';
////////////////////////////////////////////////////////////////////////////////
$idStart = 20221108082;
$idEnd = 20227225284;
////

$idMax = $idStart;
//while($idMax < $idEnd){
	$param = array('q' => 'pakistan',
				'rpp' => '100',
				'since_id' => $idMax,
				'result_type' => 'recent');

	$output = callAPI($url, $param);
	print_r($output);
	$idMax = $output['max_id'];
	//Process each output element
	for($i = 0; $i< count($output); $i++){
		insertSQLtweet('twitter', 'pakistan_gaps', $output['results'][$i]);
	}
//}


///////////////////////////////////////////////////////////////////////////////////
function insertSQLtweet($db, $table, $data){
$link = mysql_pconnect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_set_charset('utf8', $link);
mysql_select_db($db) or die(mysql_error());

//Set columns (specified case)
$fields = array('id','date','text','user_sn');
$columns = implode(',', $fields);

//Set values
$values = "'".getTweetID($data);
$values.= "','".getTweetDate($data);
$values.= "','".getTweetText($data);
$values.= "','".getScreenName($data);
$values.= "'";

$sqlInsert = "insert into $table ($columns) values ($values)";
mysql_query($sqlInsert, $link);

}
















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


/////////////////////////////////////////////////////
function getTweetID($data){
	return $data['id'];
}

function getTweetDate($data){
	$date = substr($data['created_at'], 8, 4);
	$date.= substr($data['created_at'], 5, 3);
	$date.= substr($data['created_at'], 12, 13);
	return $date;
}

function getTweetText($data){
	return $data['text'];
}

function getScreenName($data){
	return $data['from_user'];
}




?>





