<?php
//Constants
const MY_SQL_SERVER = 'research.bowdoin.edu';
const MY_SQL_USER = 'ruivoss';
const MY_SQL_PWD = 'rtwitps1';

const APP_ID = 'EzWviNHV34E2cUlA6gW2StPsuZP59fHQK5zCE37BtuCS1_glhchlIuM9z7beXvTs';
const PM_URL = 'http://wherein.yahooapis.com/v1/document';
const PF_URL = 'http://where.yahooapis.com/geocode';


//MySQL setup
$db = 'networks';
$link = mysql_connect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_set_charset('utf8', $link);
mysql_select_db($db) or die(mysql_error());

//cURL setup
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

////////////////////////////////////////////////////////////////////////////////
$table = $_POST['table'];
$subset = $_POST['subset'];
if($subset != '')
	$subset =  ' WHERE '.$subset;


$sql = "SELECT `user_id`, `location` FROM `$table` $subset";
$result = mysql_query($sql, $link);

while($row = mysql_fetch_assoc($result)){
	$data = array();
	//If empty, skip
	if($row['location'] == '')
		continue;
		
	$found = false;
	//Check coordinates
	$coord = isCoordinates($row['location']);
	if($coord !== false){
		$param = array('location' => $coord,
						'appid'=> APP_ID,
						'gflags' => 'R');
		$httpMethod = 'GET';
		$data = getPF(callAPI(PF_URL, $param, $httpMethod));
		
		//If no country is returned, don't set found to true and clear data
		if($data['country'] != '')
			$found = true;
		else
			$data = array();
	}
	//Check PlaceMaker
	if(!$found){
		$param = array('documentContent' => $row['location'],
				'documentType' => 'text/plain',
				'appid' => APP_ID);
		$httpMethod = 'POST';
		
		$data = getPM(callAPI(PM_URL, $param, $httpMethod));
		if($data['country'] != '')
			$found = true;
		else
			$data = array();
	}

	//Check PlaceFinder for zip code or state/country abbreviation
	if(!$found && isZipAb($row['location'])){	
		$param = array('location' => $row['location'],
						'appid'=> APP_ID);
		$httpMethod = 'GET';
		$data = getPF(callAPI(PF_URL, $param, $httpMethod));
	}

	//Set columns (specified case)
	$fields = array('user_lat','user_long','user_city','user_state','user_country');
	$columns = implode(',', $fields);

	//Set values
	$values = "'".$data['latitude'];
	$values.= "','".$data['longitude'];
	$values.= "','".$data['city'];
	$values.= "','".$data['state'];
	$values.= "','".$data['country'];
	$values.= "'";

	$sql = "UPDATE $table SET `latitude` = '".$data['latitude']."', `longitude` = '".$data['longitude']."', `city` = '".$data['city']."', `state` = '".$data['state']."', `country` = '".$data['country']."' WHERE `user_id` ='".$row['user_id']."'";
	mysql_query($sql, $link);

}
echo "Done";
////
curl_close($ch);
mysql_close($link);
///////////////////////////////////////////////////////////////////////////////////

//Returns a URL constructed from a $baseURL and array of parameters
function URL($baseURL, $params){
	$query = http_build_query($params);
	return $baseURL.'?'.$query;
}


//Makes call to specified $callURL with the specified $httpMethod 
function callAPI($baseURL, $params, $httpMethod = 'GET'){
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


curl_setopt($ch, CURLOPT_URL, $url);

$response = curl_exec($ch);
return $response;
}





//Exctracts name, latitude, and longitude from yahoo xml data
function getPF($xml){
	$start = strpos($xml, '<Result>');
	
	$tags[0]['field'] = 'latitude';
	$tags[0]['startStr'] = '<latitude>';
	$tags[0]['endStr'] = '</';
	
	$tags[1]['field'] = 'longitude';
	$tags[1]['startStr'] = '<longitude>';
	$tags[1]['endStr'] = '</';
	
	$tags[2]['field'] = 'city';
	$tags[2]['startStr'] = '<city>';
	$tags[2]['endStr'] = '</';
	
	$tags[3]['field'] = 'country';
	$tags[3]['startStr'] = '<countrycode>';
	$tags[3]['endStr'] = '</';
	
	$tags[4]['field'] = 'state';
	$tags[4]['startStr'] = '<statecode>';
	$tags[4]['endStr'] = '</';
	

	
	for($i = 0; $i < count($tags); $i++){
		$s = strpos($xml, $tags[$i]['startStr'], $start);
		if($s === false)
			break;
		
		$start = strlen($tags[$i]['startStr']) + $s;
		$end = strpos($xml, $tags[$i]['endStr'], $start);
		
		
		$return[$tags[$i]['field']] = substr($xml, $start, $end-$start);
	}

	return $return;
}




//Exctracts name, latitude, and longitude from yahoo xml data
function getPM($xml){
	$temp['type'] = getLocality($xml);
	
	if($temp['type'] == 3)
		$return = false;
	else{
		$start = strpos($xml, '<geographicScope>');
	
		$tags[0]['field'] = 'name';
		$tags[0]['startStr'] = '<name><![CDATA[';
		$tags[0]['endStr'] = ']]>';
	
		$tags[1]['field'] = 'latitude';
		$tags[1]['startStr'] = '<latitude>';
		$tags[1]['endStr'] = '</l';
	
		$tags[2]['field'] = 'longitude';
		$tags[2]['startStr'] = '<longitude>';
		$tags[2]['endStr'] = '</l';
	
		for($i = 0; $i < count($tags); $i++){
			$s = strpos($xml, $tags[$i]['startStr'], $start);
			if($s === false)
				break;
		
			$start = strlen($tags[$i]['startStr']) + $s;
			$end = strpos($xml, $tags[$i]['endStr'], $start);
		
		
			$temp[$tags[$i]['field']] = substr($xml, $start, $end-$start);
		}


		
		$coord = $temp['latitude'].' '.$temp['longitude'];
		$param = array('location' => $coord,
					'appid'=> APP_ID,
					'gflags' => 'R');
		$httpMethod = 'GET';
		$return = getPF(callAPI(PF_URL, $param, $httpMethod));
		//Town
		if($temp['type'] == 0)
			$return;
		//State
		else if($temp['type'] == 1)
			$return['city'] = '';
		//Country
		else{
			$return['city'] = '';
			$return['state'] = '';
		}
	}
	return $return;
}


//Returns whether user location data fits format of coordinates
function isCoordinates($text){
	$comma = strrpos($text, ',');
	if($comma !== false && ctype_digit($text[$comma-1])){
		$i = $comma-1;
		while(isDPM($text[$i-1]))
			$i--;
		$start = $i;
		
		$i = $comma;
		if(ctype_space($text[$i+1]))
			$i++;
		while(isDPM($text[$i+1]))
			$i++;
		$end = $i;
		
		$coord = substr($text, $start, $end-$start);
		//Check for 2 periods
		if(substr_count($coord, '.') == 2)
			return $coord;
		else
			return false;
	}
	else
		return false;
}


//Returns true if character is digit, a period, or a minus 
function isDPM($char){
	if(ctype_digit($char) || $char == '.' || $char == '-')
		return true;
	else
		return false;
}



//Returns whether user location data is zip code or state/country abbreviation
function isZipAb($text){
	//Zip code
	if(strlen($text) == 5 && ctype_digit($text))
		return true;
	else if(strlen($text) == 2 && ctype_alpha($text))
		return true;
	else
		return false;
}


//Returns 0 for city, 1 for state, 2 for country, 3 for undefined/blank cases
function getLocality($xml){
	$key = array('Town' => '0',
			'Suburb' => '0',
			'LocalAdmin' => '0',
			'Historical Town' => '0',
			'Zip' => '0',
			'Zone' => '0',
			'State' => '1',
			'County' => '1',
			'Country' => '2',
			'Colloqial' => '2',
			'Island' => '2',
			'Market' => '2',
			'Undefined' => '3',
			'Continent' => '3');
	
	$tags['startStr'] = '<type>';
	$tags['endStr'] = '</';
	
	$start = 0;
	for($i = 0; $i < 3; $i++){
		$s = strpos($xml, $tags['startStr'], $start);
		if($s === false){
			$result = 3;
			break;
		}
		
		$start = strlen($tags['startStr']) + $s;
		$end = strpos($xml, $tags['endStr'], $start);
		
		$type = $key[substr($xml, $start, $end-$start)];
		if(!ctype_digit($type))
			$type = 3;
		$result[$i] = $type;
	}
	return min($result);
}



?>






