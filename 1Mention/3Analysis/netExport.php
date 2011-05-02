<?php
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

//mySQL link
$link = mysql_connect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_set_charset('utf8', $link);
mysql_select_db('twitter') or die(mysql_error());
//////////////////////////////////////////////////////////
$file = 'Pajek/'.$_POST['file'];
$table = $_POST['table'];
$subset = $_POST['subset'];
if($subset != '')
	$subset =  ' AND '.$subset;

$sql = "SELECT `text`, `user_sn`, `user_city`, `user_country` FROM `$table` WHERE `text` LIKE '%@%' $subset";
$result = mysql_query($sql, $link);


//Row index
$i = 0;
//Node index
$j = 1;
while($row = mysql_fetch_assoc($result)){
	$to = getTextAtUsers($row['text']);
	//First check if text contains valid @mentions (skip row if not)
	if(count($to) == 0){
		continue;
	}
		
	$jFrom = array_search($row['user_sn'], $nodes);
	//If new user, add to verticies and increment index
	if(!$jFrom){
		$nodes[$j] = $row['user_sn'];
		$locs[$j]['country'] = getCountry($row['user_country']);
		$locs[$j]['city'] = getCity($row['user_city'], $row['user_country']);
		$jFrom = $j;
		$j++;
	}


	for($k = 0; $k < count($to); $k++){
		$jTo = array_search($to[$k]['sn'], $nodes);
		if(!$jTo){
			$nodes[$j] = $to[$k]['sn'];
			$locs[$j]['country'] = getCountry($row['user_country']);
			$locs[$j]['city'] = getCity($row['user_city'], $row['user_country']);
			$jTo = $j;
			$j++;
		}
		
		$arcs[$i]['from'] = $jFrom;
		$arcs[$i]['to'] = $jTo;
		$arcs[$i]['weight'] = $to[$k]['weight'];
		$i++;
	}
}

mysql_free_result($result);



//Get @mention locations
for($j = 1; $j <= count($locs); $j=$j+100){
	for($i = 1; $i <= count($locs); $i++){
	
	
	
	
}















//Create .net data string to export
$nl = "\r\n";
$numV = count($nodes);
$data = '*Vertices     '.$numV.$nl;
for($i = 1; $i <= $numV; $i++){
	$sn = '"'.$nodes[$i].'"';
	$line = "$i $sn";
	$data.= $line.$nl;
}

$numArcs = count($arcs);
$data.= '*Arcs'.$nl;
for($i = 0; $i < $numArcs; $i++){
	$from = $arcs[$i]['from'];
	$to = $arcs[$i]['to'];
	$weight = $arcs[$i]['weight'];
	
	$line = '';
	$line = rJ($line, $from, 7);
	$line = rJ($line, $to, 14);
	$line = rJ($line, $weight, 21);
	$data.= $line.$nl;
}
$data.= '*Edges';

$net = $file.'.net';
$handle = fopen($net, 'w') or die('Failed to open file');
fwrite($handle, $data);
fclose($handle);


//Create city .clu data string
$data = '*Partition Location_by_city'.$nl;
$data.= '*Vertices '.$numV.$nl;
for($i = 1; $i <= $numV; $i++){
	$data.= $locs[$i]['city'].$nl;
}


$cityClu = $file.'City.clu';
$handle = fopen($cityClu, 'w') or die('Failed to open file');
fwrite($handle, $data);
fclose($handle);


//Create country .clu data string
$data = '*Partition Location_by_country'.$nl;
$data.= '*Vertices '.$numV.$nl;
for($i = 1; $i <= $numV; $i++){
	$data.= $locs[$i]['country'].$nl;
}

$countryClu = $file.'Country.clu';
$handle = fopen($countryClu, 'w') or die('Failed to open file');
fwrite($handle, $data);
fclose($handle);


echo "<b>Complete <br> See files:</b> $file <br>";


////
mysql_close($link);
///////////////////////////////////////////////////////////////////////////


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



	
function getTextAtUsers($text){
	$i = 0;
	
	$sn = isRT($text);
	//Tweet is retweet
	if($sn !== false){
		$users[$i]['sn'] = $sn;
		$users[$i]['weight'] = 5;
		$i++;
		$users = array_merge($users, getRemainingUsers($text, $i, 4, $sn));
			
	}
	
	//Tweet is not retweet
	else{
		$sn = isDirect($text);
		if($sn !== false){
			$users[$i]['sn'] = $sn;
			$users[$i]['weight'] = 1;
			$i++;
			$users = array_merge($users, getRemainingUsers($text, $i, 2, $sn));
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
			$users[$i]['sn'] = $user;
			$users[$i]['weight'] = $weight;
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
				$users[$i]['sn'] = $user;
				$users[$i]['weight'] = $weight;
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




//Searches country table for ISO; returns key if found, 0 if not
function getCountry($iso){
	global $link;
	$sql = "SELECT `numcode` FROM `country` WHERE `iso` LIKE '$iso'";
	$result = mysql_query($sql, $link);
		
	$return = mysql_result($result, 0);
	mysql_free_result($result);
	if($return === false)
		return 0;
	else
		return $return;
}


//Searches city table for city name; returns key if found, 0 if not
function getCity($city, $iso){
	global $link;
	if($city == ''){
		echo "$city, $iso => 0<br>";
		return 0;
	}
	else{
		$sql = "SELECT `ID` FROM `CityMod` WHERE (`Name` LIKE '%$city%' AND `iso` LIKE '$iso')";
	
		$result = mysql_query($sql, $link);
			
		$return = mysql_result($result, 0);
		mysql_free_result($result);
		if($return == ''){
			echo "<strong>$city, $iso => 0</strong><br>";
			return 0;
		}
		else{
			echo "$city, $iso => $return<br>";
			return $return;
		}
	}
}






?>