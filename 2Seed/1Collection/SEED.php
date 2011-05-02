<?php
//Constants
$username = 'SocialNetLab';
$password = 'p0larbears';

$consumer_key = 'Ec81fCY8oP4vbdRZPr6mMg';
$consumer_secret = 'BtLFzPpff7reEH2DgcvBiSLByDhZcnQMMecXmQQ6jxE';
$oauth_token = '226182690-ROF1keHSl1hjkUzWpeK2BiKs6ia0hSB1gaf5ed6Q';
$oauth_token_secret = '1SvM5pOo2HQoXeVmndSAT9gBoxFFAltqaB8MfbzM90';

$my_sql_server = 'localhost';
$my_sql_user = 'root';
$my_sql_pwd = 'root';


//php setup


//mySQL setup
$db = 'SEEDS';
$link = mysql_connect($my_sql_server, $my_sql_user, $my_sql_pwd) or die(mysql_error());
mysql_set_charset('utf8', $link);
mysql_select_db($db) or die(mysql_error());



//CURL setup
$ch = curl_init();








////MAIN////////////////////////////////////////////////////////////////////////////
//Input
$seed = $_POST['seed'];
$distMax = $_POST['dist'];
//  //

for($dist = 0; $dist < $distMax; $dist++){
	if($dist == 0){
		$input['id'] = snLookup($seed);
		$input['dist'] = 0;
		$input['verified'] = -1;
		dbWrite('Users', $input);
	}
	echo "Distance $dist complete <br>";
	addAllArcs($dist);

}
echo "Distance $dist complete; done<br>";




















//
mysql_close();
curl_close($ch);
//FUNCTIONS/////////////////////////////////////////////////////////////////////////////////
//Gets all arcs to/from $dist users from/to $dist+1 users
function addAllArcs($dist){
	$table = 'Users';
	$where['dist'] = $dist;
	$result = dbRead('Users', $where);
	
	while($row = mysql_fetch_assoc($result)){
		addArcs($row['id'], $dist);
	}
	mysql_free_result($result);
}

//Returns all arcs for a particular user with some distance
function addArcs($id, $dist){
	//Arcs to id
	$url = 'http://api.twitter.com/1/followers/ids.json';
	$param['user_id'] = $id;
	$param['cursor'] = -1;
	
	while($param['cursor'] != 0){
		$apiArray = callAPI($url, $param);
		
		if($apiArray !== false){
			$param['cursor'] = $apiArray['next_cursor_str'];
			foreach($apiArray['ids'] as $k => $v){
				$usersInput['id'] = $v;
				$usersInput['dist'] = $dist + 1;
				$usersInput['verified'] = -1; 
				dbWrite('Users', $usersInput);
				
				$arcsInput['id_to'] = $id;
				$arcsInput['id_from'] = $v;
				dbWrite('Arcs', $arcsInput);
			}
		}
		else
			break;
	}
	
	//Arcs away from id
	$url = 'http://api.twitter.com/1/friends/ids.json';
	$param['user_id'] = $id;
	$param['cursor'] = -1;
	$apiArray = callAPI($url, $param);

	while($param['cursor'] != 0){
		$apiArray = callAPI($url, $param);
		
		if($apiArray !== false){
			$param['cursor'] = $apiArray['next_cursor_str'];
			foreach($apiArray['ids'] as $k => $v){
				$usersInput['id'] = $v;
				$usersInput['dist'] = $dist + 1;
				$usersInput['verified'] = -1; 
				dbWrite('Users', $usersInput);
				
				$arcsInput['id_to'] = $v;
				$arcsInput['id_from'] = $id;
				dbWrite('Arcs', $arcsInput);
			}
		}
		else
			break;
	}
}





//Returns the id associated with a particular screenname
function snLookup($sn){
	$url = "http://api.twitter.com/1/users/lookup.json";
	$params['screen_name'] = $sn;
	$httpMethod = 'GET';
	$auth = true;
	
	$return = callAPI($url, $params, $httpMethod, $auth);
	return $return[0]['id'];
}






































//MODULES//////////////////////////////////////////////////////////////////////////

//MYSQL FUNCTIONS//
//MySQL wrapper for 'Insert'; $input should be array; $cond = true will only write if
//an entry with $input does not already exist
function dbWrite($table, $input, $cond = false){
	$input = escapeArray($input);
	
	$table =  mysql_real_escape_string($table);
	$cols = implode(", ", array_keys($input));
	$values = "'".implode("', '", $input)."'";
	
	if($cond === false){
		$format = "INSERT INTO %s (%s) VALUES (%s)";
		$sql = sprintf($format, $table, $cols, $values);
	}
	else{
		$format = "INSERT INTO %s (%s) SELECT %s FROM dual WHERE NOT EXISTS (SELECT * FROM %s WHERE %s)";
		$sql = sprintf($format, $table, $cols, $values, $table, updateFormatArray($input, 'WHERE'));
	}
		
	mysql_query($sql);
}



//MySQL: wrapper for 'Update'
function dbEdit($table, $update, $where){
	$update = escapeArray($update);
	$where = escapeArray($where);
	$format = "UPDATE %s SET %s WHERE %s";
	
	$table =  mysql_real_escape_string($table);
	$set = updateFormatArray($update);
	$where = updateFormatArray($where, 'WHERE');
	
	$sql = sprintf($format, $table, $set, $where);
	mysql_query($sql);
}




//MySQL: wrapper for 'Select'; returns result
function dbRead($table, $where){
	$where = escapeArray($where);
	$where = updateFormatArray($where, 'WHERE');
	$format = "SELECT * FROM %s WHERE %s";
	
	$sql = sprintf($format, $table, $where);

	return mysql_query($sql);
}



//MySQL: escapes all keys and values of an array for insertion into db; returns escaped array
function escapeArray($array){
	$escapedArray = array();
	foreach($array as $k => $v){
		$escapedArray[mysql_real_escape_string($k)] = mysql_real_escape_string($v);
	}
	
	return $escapedArray;
}


//MySQL: returns string formatted for sql set or where
function updateFormatArray($array, $type = "SET"){
	foreach($array as $k => $v)
		$formattedArray[] = "$k='$v'";
	
	if($type == 'SET')	
		$glue = ', ';
	else 
		$glue = 'AND ';
		
	return implode($glue, $formattedArray);
}








//CURL FUNCTIONS//
//Makes call to specified $callURL with the specified $httpMethod; 
//returns false if unavoidable error is returned
function callAPI($baseURL, $params, $httpMethod = 'GET', $auth = true){
	$maxAttempts = 5;
	global $ch;

	//Formats http request for GET or POST
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
	
	//Set options, url for request	
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
		
	
	//Execution (with error logging/handling with max failed attempts)	
	$attempt = 0;
	do{
		//Add OAuth header if needed (not a static entity--includes timestamp)
		if($auth){
			$authHead = authHead($baseURL, $params, $httpMethod);	
			curl_setopt($ch, CURLOPT_HTTPHEADER, $authHead);
		}

		//Make request
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$attempt = errorHandler($httpCode, $attempt, $response);
	} while($attempt > 0 && $attempt < $maxAttempts);	
		
	//Decoding and returning (based on if call was successful)
	if($attempt == 0)
		$output = json_decode($response, true);
	else 
		$output =  false;
	return $output; 
}







//Returns a URL constructed from a $baseURL and array of parameters
function URL($baseURL, $params){
	$query = http_build_query($params);
	return $baseURL.'?'.$query;
}



//Returns an OAuth signature (not url encoded) given a URL and parameter array
function signature($baseURL, $params, $httpMethod = 'GET'){
	global $consumer_secret;
	global $consumer_key;
	global $oauth_token;
	global $oauth_token_secret;
	
	$sig_key = urlencode($consumer_secret).'&'.urlencode($oauth_token_secret);
	$signArray = array('oauth_consumer_key' => $consumer_key,
					'oauth_nonce' => md5(posix_getpid().microtime().mt_rand()),
					'oauth_signature_method' => 'HMAC-SHA1',				
					'oauth_timestamp' => time(),
					'oauth_token' => $oauth_token,
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
	global $consumer_secret;
	global $consumer_key;
	global $oauth_token;
	global $oauth_token_secret;
	$sig_key = urlencode($consumer_secret).'&'.urlencode($oauth_token_secret);
	$signArray = array('oauth_consumer_key' => $consumer_key,
					'oauth_nonce' => md5(posix_getpid().microtime().mt_rand()),
					'oauth_signature_method' => 'HMAC-SHA1',				
					'oauth_timestamp' => time(),
					'oauth_token' => $oauth_token,
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


//Writes to a Curl error log with timestamp
function curlLog($string){
	$nl = "\r\n";
	$path = '/Users/slongwel/Desktop/CURL_Log.txt';
	$lh = fopen($path, 'a');
	fwrite($lh, date('\[j\-M G:i:s]').'  '.$string.$nl);
	fclose($lh);
}

//Handles http errors appropriately
function errorHandler($httpCode, $attempt, $response){
	//200: OK
	//Don't log (unless a retry); don't try again
	if($httpCode == 200){
		if($attempt > 0){
			curlLog($httpCode.': Successful retry');
		}
		$attempt = 0;
	}
	
	//304: Not Modified (No new data)
	//Log; sleep 2sec and retry
	else if($httpCode == 304){
		$attempt++;
		curlLog($httpCode.': Failed attempt #'.$attempt.'  '.$response);
		sleep(2);
	}
		
	//400: Bad request (Rate limit OR invalid request) (Disambiguate using callAPI??)
	//Log; sleep 1hr and retry
	else if($httpCode == 400){
		$attempt++;
		curlLog($httpCode.': Failed attempt #'.$attempt.'  '.$response);
		sleep(3601);
	}

	//401: Unauthorized (Incorrect credentials OR probably protected account)
	//Log; don't retry (provide ID in log??)
	else if($httpCode == 401){
		$attempt++;
		curlLog($httpCode.': Likely protected  '.$response);
		$attempt = -1;
	}
	
	//502: Bad Gateway (Twitter is down OR probably the request was too big) (Disambiguate??)
	//Log; sleep 1 sec and retry with cursor
	else if($httpCode == 502){
		$attempt++;
		curlLog($httpCode.': Failed attempt #'.$attempt);
		sleep(1);
	}	
	
	
	//Other: 404, 406, 420, 500, 503
	//Log; sleep 2sec and retry
	else{
		$attempt++;
		curlLog('Other '.$httpCode.': Failed attempt #'.$attempt.'  '.$response);
		sleep(2);
	}
	
	
	return $attempt;
}



?>