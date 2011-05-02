<?php
//1: Twitter constants
const USERNAME = 'PolarSociology';
const PASSWORD = 'bowdoincollege';
const CALLBACK = 'http://139.140.68.239:8888';

const CONSUMER_KEY = '8rGqiXg7uP2fhBpsjy75w';
const CONSUMER_SECRET = 'ruqsmmhQQFolEJSRdcJyZkYUYPuBpE5R0T6QIsU';
const OAUTH_TOKEN = '177293503-FXvTFHxojNSGsWBrLR1h2ug30wlBuplMtlk2A3AU';
const OAUTH_TOKEN_SECRET = 'kp6VYlJwNN5kPpj5511wqiP7SFtt1xS2CIfUxVfY';

//mySQL constants
const MY_SQL_SERVER = 'localhost';
const MY_SQL_USER = 'root';
const MY_SQL_PWD = 'root';

//Twitter time (roughly)
date_default_timezone_set('UTC');

//mySQL setup
$db = 'twitter';
$table = 'test';
$link = mysql_pconnect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_set_charset('utf8', $link);
mysql_select_db($db) or die(mysql_error());
////////////////////////////////////////////////////////////////////////////////

$track = $_POST['track'];
$time = $_POST['time'];

//Get global searchID
$sql = "SELECT MAX(`search_id`) FROM `searches` ";
$result =  mysql_query($sql, $link);
$searchID = 1+mysql_result($result, 0);
mysql_free_result($result);


streamAPI($track, $time);






//////////////////////////////////////////////////////////////
function streamAPI($track, $time){
	$instream = initializeStream($track);

	echo '<strong>Runtime:</strong> '.$time.' seconds<br>';
	echo '<strong>Start:</strong> '.myDate().'<br>';
	insertSQLsearch();
	$endTime = time()+$time;
	
	//Stream loop
	while((time() < $endTime)) {
		//Check for errors before proceeding
		$instream = httpErrorHandler($instream, $track);	

		
		if(!($line = stream_get_line($instream, 20000, "\n"))) {
			continue;
		}
		
		else{
			$tweet = json_decode($line, true);
			insertSQLtweet($tweet);
			flush();
		}
	}
	echo '<br><strong>Final</strong>: '.myDate().'<br>';
	fclose($instream);
}	


function initializeStream($track){
	$opts = array('http'=>array(
				  'method'	=>	"POST",
				  'content'	=>	'track='.$track));
	$context = stream_context_create($opts);
	return fopen('http://'.USERNAME.':'.PASSWORD.'@stream.twitter.com/1/statuses/filter.json','r' ,false, $context);
}





//Closes stream if network error is returned and restarts after pausing
function networkErrorHandler($handle, $track){
	//Initial pause time, in microseconds
	$pause = 250000;
	$status = stream_get_meta_data($handle);
	
	while($status['wrapper_data'][0] != 'NETWORK ERROR'){
		echo myDate().' Network Error: Pausing for '.((float)($pause)/1000000).' seconds';
		fclose($handle);
		usleep($pause);
		//Increment pause, cap at 10 seconds
		if($pause <= 10000000)
			$pause = $pause+250000;
		$handle = initializeStream($track);	
		$status = stream_get_meta_data($handle);
	}
	return $handle;
}




//Closes stream if HTTP error is returned and restarts after pausing
function httpErrorHandler($handle, $track){
	//Initial pause time, in seconds
	$pause = 5;
	$status = stream_get_meta_data($handle);
	
	while($status['wrapper_data'][0] != 'HTTP/1.1 200 OK'){
		echo '<strong>Error:</strong> '.myDate().' HTTP ('.$status['wrapper_data'][0].'), pausing for '.$pause.' seconds';
		fclose($handle);
		sleep($pause);
		//Increment pause exponentially, cap at 160 seconds
		if($pause <= 240)
			$pause = 2*$pause;	
		$handle = initializeStream($track);	
		$status = stream_get_meta_data($handle);
	}
	return $handle;
}





function droppedHandler($handle, $track){
	if(feof($handle))
		$handle = initializeStream($track);
	return $handle;	
}













//Inserts a tweet into the mySQL database
function insertSQLtweet($data){
global $link;
global $table;

//Set columns (specified case)
$fields = array('id','date','source','text','latitude','longitude','user_id','user_sn','user_loc','search_id');
$columns = implode(',', $fields);

//Set values
$values = "'".getTweetID($data);
$values.= "','".getTweetDate($data);
$values.= "','".getTweetSource($data);
$values.= "','".getTweetText($data);
$values.= "','".getTweetLat($data);
$values.= "','".getTweetLong($data);
$values.= "','".getUserID($data);
$values.= "','".getScreenName($data);
$values.= "','".getUserLocation($data);
$values.= "','".getSearchID();
$values.= "'";

$sqlInsert = "insert into $table ($columns) values ($values)";
mysql_query($sqlInsert, $link);

}


//Inserts search info into the mySQL database
function insertSQLsearch(){
global $link;
$table = 'searches';

//Set columns (specified case)
$fields = array('terms');
$columns = implode(',', $fields);

//Set values
$values = "'".getSearchTerms()."'";

$sqlInsert = "insert into $table ($columns) values ($values)";
mysql_query($sqlInsert, $link);

}








//Get tweet info
function getTweetID($data){
	return $data['id'];
}


function getTweetDate($data){
	$date = substr($data['created_at'], 4, 6);
	$date.= substr($data['created_at'], -5);
	$date.= substr($data['created_at'], 10, 9);
	return $date;
}


function getTweetSource($data){
	if($data['source']==='web')
		return $data['source'];
	else{
		$a = 1+strpos($data['source'],'>',10);
		$b = strpos($data['source'],'<',$a);;
		$source = substr($data['source'],$a,$b-$a);
		return $source;
	}
}


function getTweetText($data){
	return $data['text'];
}


function getTweetLat($data){
	if($data['coordinates'] != '')
		return $data['coordinates']['coordinates'][1];
	else if($data['place'] != ''){
		$a = $data['place']['bounding_box']['coordinates'][0][0][1];
		$b = $data['place']['bounding_box']['coordinates'][0][2][1];
		return (($a+$b)/2);
	}
	else
		return;
}


function getTweetLong($data){
	if($data['coordinates'] != '')
		return $data['coordinates']['coordinates'][0];
	else if($data['place'] != ''){
		$a = $data['place']['bounding_box']['coordinates'][0][0][0];
		$b = $data['place']['bounding_box']['coordinates'][0][2][0];
		return (($a+$b)/2);
	}
	else
		return;
}


//Get user info
function getUserID($data){
	return $data['user']['id'];
}

function getScreenName($data){
	return $data['user']['screen_name'];
}

function getUserName($data){
	return $data['user']['name'];
}

function getUserLocation($data){
	return $data['user']['location'];
}


//Get search info
function getSearchID(){
	global $searchID;
	return $searchID;
	
}

function getSearchTerms(){
	global $track;
	return $track;
}







function myDate(){
	return date('M j, G:i:s');
}





?>