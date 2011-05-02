<?php
//Constants
$my_sql_server = 'research.bowdoin.edu';
$my_sql_user = 'ruivoss';
$my_sql_pwd = 'rtwitps1';

//Twitter time (roughly)
date_default_timezone_set('UTC');

//cURL setup
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

//mySQL setup
$db = 'twitter';
$table = 'pakistan';
$link = mysql_connect($my_sql_server, $my_sql_user, $my_sql_pwd) or die(mysql_error());
//mysql_set_charset('utf8', $link);
mysql_select_db($db) or die(mysql_error());
////////////////////////////////////////////////////////////////////////////////


$sql = "SELECT `id`, `text` FROM `$table` WHERE `text` LIKE '%http://%'";
$result = mysql_query($sql, $link);


while($row = mysql_fetch_assoc($result)){
	insertURL($row);
}



////
mysql_close($link);
curl_close($ch);
////////////////////////////////////////////////////////////////////////////////
//Takes shortened URL's and converts them to full ones (false if there are none)
function getFullURLs($text){
	$urls = getURLs($text);
	if($urls === false)
		return false;
	for($i = 0, $k = 0; $i < count($urls); $i++){
		$url = getFullURL($urls[$i]);
		if($url != ''){
			$return[$k] = $url;
			$k++;
		}
	}
	return $return;
}


function getFullURL($url){
	global $ch;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_exec($ch);
	if(curl_getinfo($ch, CURLINFO_HTTP_CODE) == 0)
		return false;
	else
		return removeHttp(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
}

//Calls API with short URL, then parses header for full link
function getFullURL2($url){
	global $ch;
	curl_setopt($ch, CURLOPT_URL, $url);
	$header = curl_exec($ch);
	if($header === false)
		$return = false;
	else if(httpCode($header) <= 200)
		$return = removeHttp($url);
	else{
		$start = 9 + strpos($header, 'Location:');

		$tags['startStr'] = '//';
		$tags['endStr'] = PHP_EOL;
	
		$s = strpos($header, $tags['startStr'], $start);
		
		$start = strlen($tags['startStr']) + $s;
		$end = strpos($header, $tags['endStr'], $start);
		
		$return = substr($header, $start, $end-$start);
	}
	if(substr($return, 0, 6) != 'bit.ly')
		return $return;
	else
		return getFullURL('http://'.$return);
}

//Returns array of URLs in a tweet, or false if none
function getURLs($text){
	$tags = array('http://', 'https://');
	$urls = false;
	$k = 0;
	//For each URL tag
	for($i = 0; $i < count($tags); $i++){
		$start = strpos($text, $tags[$i]);
		while($start !== false){
			for($end = $start + strlen($tags[$i]); $end <= strlen($text) && !ctype_space($text[$end]); $end++){	
			}
			$url = substr($text, $start, $end-$start);
			if(removeHttp($url) != ''){
				$urls[$k] = substr($text, $start, $end-$start); 
				$k++;
			}
			$start = strpos($text, $tags[$i], $end);
		}
	}		
	return $urls;	
}


//Parses header for error number
function httpCode($header){
	$space = ' ';
	$start = 1+strpos($header, $space);
	$end = strpos($header, $space, $start);
	return substr($header, $start, $end-$start);
}

function removeHttp($url){
	$pos = strpos($url, '//') + 2;
	return substr($url, $pos);
}


function insertURL($row){
	global $link;
	$table = 'urls2';
	
	$urls = getFullURLs($row['text']);
	if($urls[0] != ''){
		for($i = 0; $i<count($urls); $i++){
			//Make connections
			//Set columns (specified case)
			$fields = array('url_id','url','tweet_id');
			$columns = implode(',', $fields);
			
			//Set values
			$values = "NULL";
			$values.= " ,'".$urls[$i];
			$values.= "','".$row['id'];
			$values.= "'";

			$sqlInsert = "insert into $table ($columns) values ($values)";
			mysql_query($sqlInsert);
		}
	}
}


?>