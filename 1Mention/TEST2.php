<?php
//1: Twitter constants
const USERNAME = 'PolarSociology';
const PASSWORD = 'bowdoincollege';

const CONSUMER_KEY = '8rGqiXg7uP2fhBpsjy75w';
const CONSUMER_SECRET = 'ruqsmmhQQFolEJSRdcJyZkYUYPuBpE5R0T6QIsU';
const OAUTH_TOKEN = '177293503-FXvTFHxojNSGsWBrLR1h2ug30wlBuplMtlk2A3AU';
const OAUTH_TOKEN_SECRET = 'kp6VYlJwNN5kPpj5511wqiP7SFtt1xS2CIfUxVfY';

//mySQL constants
const MY_SQL_SERVER = 'localhost';
const MY_SQL_USER = 'root';
const MY_SQL_PWD = 'root';


//mySQL setup
$dbFrom = 'twitter';
$dbTo = 'pakistan_network';
$tableFrom = 'pakistan';
$link = mysql_connect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_set_charset('utf8', $link);

//CURL setup
$ch = curl_init();

////////////////////////////////////////////////////////////////////////////////
$subset = '';
if($subset != '')
	$subset =  ' AND '.$subset;

	
	
mysql_select_db($dbFrom) or die(mysql_error());
$sqlFrom = "SELECT * FROM `$tableFrom`";
$result = mysql_query($sqlFrom, $link);

mysql_select_db($dbTo) or die(mysql_error());
$j = 0;
while($row = mysql_fetch_assoc($result)){
	insertTweet($row);
	insertUser($row);
	insertMentions($row);
	
	$j++;
	if($j >= 50)
		break;
}





//
mysql_close($link);
curl_close($ch);
////////////////////////////////////////////////////////////////////////////////
//Inserts a tweet into the networkDB
function insertTweet($row){
	global $link;
	$table = 'tweets';
	
	//Set columns (specified case)
	$fields = array('tweet_id','date','dbDate','source','text','latitude','longitude','user_id','search_id');
	$columns = implode(',', $fields);
	
	//Set values
	$values = "'".$row['id'];
	$values.= "','".$row['date'];
	$values.= "','".$row['dbDate'];
	$values.= "','".$row['source'];
	$values.= "','".$row['text'];
	$values.= "','".$row['latitude'];
	$values.= "','".$row['longitude'];
	$values.= "','".$row['user_id'];
	$values.= "','".$row['search_id'];
	$values.= "'";
	
	$sqlInsert = "insert into $table ($columns) values ($values)";
	mysql_query($sqlInsert);
}


//Inserts a user into the networkDB
function insertUser($row){
	global $link;
	$table = 'users';
	
	//Set columns (specified case)
	$fields = array('user_id','sn','location','search_id');
	$columns = implode(',', $fields);
	
	//Set values
	$values = "'".$row['user_id'];
	$values.= "','".$row['user_sn'];
	$values.= "','".$row['user_loc'];
	$values.= "','".$row['search_id'];
	$values.= "'";
	
	$sqlInsert = "insert into $table ($columns) values ($values)";
	mysql_query($sqlInsert);
}

//Inserts any @mentions present in a particular row and user info for @mentioned users 
function insertMentions($row){
	global $link;
	$table = 'mentions';
	
	$mentions = completeMentionInfo($row['text']);

	if($mentions[0]['user_id'] != ''){
		for($i = 0; $i<count($mentions); $i++){
			//Make connections
			//Set columns (specified case)
			$fields = array('mention_id','weight','user_id_from','user_id_to', 'tweet_id', 'search_id');
			$columns = implode(',', $fields);
			
			//Set values
			$values = "NULL";
			$values.= " ,'".$mentions[$i]['weight'];
			$values.= "','".$row['user_id'];
			$values.= "','".$mentions[$i]['user_id'];
			$values.= "','".$row['id'];
			$values.= "','".$row['search_id'];
			$values.= "'";

			$sqlInsert = "insert into $table ($columns) values ($values)";
			mysql_query($sqlInsert);
			
			//Add @mention users
			$mentions[$i]['search_id'] = $row['search_id'];
			insertUser($mentions[$i]);
			
			
		}
	}
}






//Returns all user info through CURL calls to the REST API
function completeMentionInfo($text){
	$users = getTextAtUsers($text);
	if(count($users) == 0)
		return false;
	else{
		$snList = implode(',', $users['sn']);
		$url = 'http://api.twitter.com/1/users/lookup.json';
		$param = array('screen_name' => $snList);
		$httpMethod = 'POST';
		$auth = true;
	
		$return = callAPI($url, $param, $httpMethod, $auth);
		for($i = 0; $i<count($return); $i++){
			$j = array_search($return[$i]['id'], $users['sn']);
			$mentions[$i]['user_id'] = $return[$i]['id'];
			$mentions[$i]['user_sn'] = $return[$i]['screen_name'];
			$mentions[$i]['user_loc'] = $return[$i]['location'];
			$mentions[$i]['weight'] = $users['weight'][$j];
		}
		return $mentions;
	}
}


//Returns sn of direct @mention if direct @mention; else false
function isDirect($text){
	$sn = false;
	if($text[0] == '@')
		$sn = getAtUser($text, 0);
	return $sn;
}


//Returns sn of RT if retweet; else returns false. Case sensitive
function isRT($text){
	$conditions = array('RT @', 'RT: @', 'retweeting @', 'retweet @', 'via @', 'HT @');
	$sn = false;
	for($i = 0; $i < count($conditions) && !$sn; $i++){
		if($i <= 3)
			$pos = strpos($text, $conditions[$i]);
		else
			$pos = strrpos($text, $conditions[$i]);
		
		if($pos === false)
			continue;
		else
			$sn = getAtUser($text, $pos+strlen($conditions[$i])-1);
	}		
	return $sn;	
}



//Returns user following string index of @; returns false if blank
function getAtUser($text, $index){
	$index++;
	$user = '';
	while(isAl_Num(substr($text, $index,1))){
		$user.= substr($text, $index,1);
		$index++;
	}
	if($user === '')
		$user = false;
	return $user;
}
	
//Returns true if character is alpha-numeric or underscore
function isAl_Num($char){
	$us = '_';
	if($char == $us || ctype_alnum($char))
		return true;
	else
		return false;
}	



//Returns associative array of @mentioned users and their weights	
function getTextAtUsers($text){
	$i = 0;
	
	$sn = isRT($text);
	//Tweet is retweet
	if($sn !== false){
		$users['sn'][$i] = $sn;
		$users['weight'][$i] = 5;
		$i++;
		$users = array_merge_recursive($users, getRemainingUsers($text, $i, 4, $sn));
			
	}
	
	//Tweet is not retweet
	else{
		$sn = isDirect($text);
		if($sn !== false){
			$users['sn'][$i] = $sn;
			$users['weight'][$i] = 1;
			$i++;
			$users = array_merge_recursive($users, getRemainingUsers($text, $i, 2, $sn));
		}
		else{
			$users = getRemainingUsers($text, $i, 3);
		}
	}
	return $users;
}


//Returns an array of users who are @mentioned in a tweet
function getRemainingUsers($text, $i, $weight, $found = ''){
	$users =  array();
	if($weight == 2){
		$pos = strlen($found) + 2;
		$user = isDirect(substr($text, $pos));
		while($user !==  false){
			$users['sn'][$i] = $user;
			$users['weight'][$i] = $weight;
			$i++;
			$pos = $pos + strlen($user) + 2;
			$user = isDirect(substr($text, $pos));
		}
		$weight = 3;
		$text = substr($text, $pos);
	}
	
	for($j = 0; $j < strlen($text); $j++){
		if($text[$j] == '@'){
			$user = getAtUser($text, $j);
			$j = $j+strlen($user);
			if($user !== false && $user !== $found){
				$users['sn'][$i] = $user;
				$users['weight'][$i] = $weight;
				$i++;
			}
		}
	}
	return $users;		
}




//Places last character of string at specified $pos (0,1,2...) in line
function rJ($line, $str, $pos){
	$space = " ";
	$start = strlen($line);
	$spaceStop = $pos - strlen($str);
	for($i = $start; $i < $spaceStop; $i++)
		$line.= $space;

	$line.=$str;
	return $line;
}








//CURL//

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
global $ch;
	
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
return $output; 
}





?>