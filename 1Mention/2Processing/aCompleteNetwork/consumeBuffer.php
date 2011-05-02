<?php
//1: Twitter constants
const USERNAME = 'PolarSociology';
const PASSWORD = 'bowdoincollege';

const CONSUMER_KEY = '8rGqiXg7uP2fhBpsjy75w';
const CONSUMER_SECRET = 'ruqsmmhQQFolEJSRdcJyZkYUYPuBpE5R0T6QIsU';
const OAUTH_TOKEN = '177293503-FXvTFHxojNSGsWBrLR1h2ug30wlBuplMtlk2A3AU';
const OAUTH_TOKEN_SECRET = 'kp6VYlJwNN5kPpj5511wqiP7SFtt1xS2CIfUxVfY';

//mySQL constants
const MY_SQL_SERVER = 'research.bowdoin.edu';
const MY_SQL_USER = 'ruivoss';
const MY_SQL_PWD = 'rtwitps1';


//mySQL setup
$dbFrom = 'twitter';
$dbTo = 'networks';
$tableFrom = 'pakistan';
$link = mysql_connect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_set_charset('utf8', $link);

//CURL setup
$ch = curl_init();
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
$subset = '';
if($subset != '')
	$subset =  ' AND '.$subset;

	
	
mysql_select_db($dbFrom) or die(mysql_error());
$sqlFrom = "SELECT * FROM `$tableFrom`";
$result = mysql_query($sqlFrom, $link);

mysql_select_db($dbTo) or die(mysql_error());

while($row = mysql_fetch_assoc($result)){
	insertTweet($row);
	insertUser($row);
	insertMentionSN($row);
}


//Add @mentioned user info
completeMentions();

//Add mention statuses to users
addMentionStatuses();


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
	$values.= "','".addSlashes($row['text']);
	$values.= "','".$row['latitude'];
	$values.= "','".$row['longitude'];
	$values.= "','".$row['user_id'];
	$values.= "','".$row['search_id'];
	$values.= "'";
	
	$sqlInsert = "insert into $table ($columns) values ($values)";
	mysql_query($sqlInsert);
}

function insertMentionSN($row){
	global $link;
	$table = 'mentions';
	
	$users = getTextAtUsers($row['text']);

	if($users['sn'][0] != ''){
		for($i = 0; $i<count($users['sn']); $i++){
			//Make connections
			//Set columns (specified case)
			$fields = array('weight','user_id_from','user_sn_to','tweet_id','search_id');
			$columns = implode(',', $fields);

			//Set values
			$values = "'".$users['weight'][$i];
			$values.= "','".$row['user_id'];
			$values.= "','".$users['sn'][$i];
			$values.= "','".$row['id'];
			$values.= "','".$row['search_id'];
			$values.= "'";
			
			$sqlInsert = "insert into $table ($columns) VALUES ($values)";
			mysql_query($sqlInsert, $link);
		}
	}
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



function completeMentions(){
	global $link;
	global $ch;
	$table = 'mentions';
	
	$sqlFrom = "SELECT COUNT(*) FROM `$table`";
	$result = mysql_query($sqlFrom, $link);
	$count = mysql_fetch_assoc($result);
	$delCount = 0;
	
	for($i=0; $i<$count['COUNT(*)']; $i=$i+100){
		$sqlFrom = "SELECT `user_sn_to`, `mention_id`,`search_id` FROM `$table` LIMIT $i, 100";
		$result = mysql_query($sqlFrom, $link);
		$j = 0;
		while($row = mysql_fetch_assoc($result)){
			$input['mention_id'][$j] = $row['mention_id'];
			$input['search_id'][$j] = $row['search_id'];
			$input['user_sn_to'][$j] = $row['user_sn_to'];
			$j++;
		}

		$snList = implode(',', $input['user_sn_to']);
		$url = 'http://api.twitter.com/1/users/lookup.json';
		$param = array('screen_name' => $snList);
		$httpMethod = 'POST';
		$auth = true;
	
		//Call API with error handling
		$retry = true;
		while($retry){
			$output = callAPI($url, $param, $httpMethod, $auth);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			echo $httpCode.': '.$i."<br>";
			if($httpCode >=200 && $httpCode <300)
				$retry = false;
			else if($httpCode == 400)
				sleep(3601);
			else 
				sleep(3);
		}
		
		
		
		
		for($j = 0; $j<count($input['mention_id']); $j++){
			$k = searchOutput($input['user_sn_to'][$j], $output);

			if($k === false){
				$toDelete[$delCount] = $input['mention_id'][$j];
				$delCount++;
			}
			else{
				$userInfo['user_id'] = $output[$k]['id'];
				$userInfo['user_sn'] = $output[$k]['screen_name'];
				$userInfo['user_loc'] = $output[$k]['location'];
				$userInfo['search_id'] = $input['search_id'][$j];
				insertUser($userInfo);

				$sqlUp = "UPDATE $table SET `user_id_to` = '".$userInfo['user_id']."' WHERE `mention_id`=".$input['mention_id'][$j];
				mysql_query($sqlUp, $link);
			}	
		}
	}
	
	//Delete nonexistent sn's
	for($i=0; $i<count($toDelete); $i++){
		$sqlDel = "DELETE FROM $table WHERE `mention_id`=".$toDelete[$i];
		mysql_query($sqlDel, $link);
	}
}


function searchOutput($needle, $output){
	for($i=0; $i<count($output); $i++){
		if($output[$i]['screen_name'] == $needle){
			return $i;
		}
	}
	return false;
}

//0=Not involved in mention, 1=From only, 2=To only, 3=Both
function addMentionStatuses(){
	global $link;
	$tableFrom = 'mentions';
	$table = 'users';
	
	//From users
	$sqlFrom = "SELECT DISTINCT `user_id_from` FROM `$tableFrom`";
	$result = mysql_query($sqlFrom, $link);
	while($row = mysql_fetch_assoc($result)){
		$sqlUp = "UPDATE $table SET `mention_status` = '1' WHERE `user_id`=".$row['user_id_from'];
		mysql_query($sqlUp, $link);
	}
	
	//To users (and from/to)
	$sqlFrom = "SELECT DISTINCT `user_id_to` FROM `$tableFrom`";
	$result = mysql_query($sqlFrom, $link);
	while($row = mysql_fetch_assoc($result)){
		$sqlUp = "UPDATE $table SET `mention_status` = '2' WHERE `user_id`=".$row['user_id_to']." AND `mention_status`=0";
		mysql_query($sqlUp, $link);
		
		$sqlUp = "UPDATE $table SET `mention_status` = '3' WHERE `user_id`=".$row['user_id_to']." AND `mention_status`=1";
		mysql_query($sqlUp, $link);
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