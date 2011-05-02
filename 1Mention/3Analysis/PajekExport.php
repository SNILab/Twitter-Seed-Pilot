<?php
//mySQL constants
const MY_SQL_SERVER = 'research.bowdoin.edu';
const MY_SQL_USER = 'ruivoss';
const MY_SQL_PWD = 'rtwitps1';

//mySQL link
$link = mysql_connect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_set_charset('utf8', $link);
mysql_select_db('networks') or die(mysql_error());
//////////////////////////////////////////////////////////
$file = 'pakistan';
$subset = '';
if($subset != '')
	$subset =  ' AND '.$subset;

//Get Arcs, Nodes by going through mentions:
$nodes = array();

$sql = "SELECT `user_id_from`, `user_sn_to`, `weight` FROM `mentions`";
$result = mysql_query($sql, $link);
$i = 1; //arc index
$j = 1;//node index
while($row = mysql_fetch_assoc($result)){	
	$sql = "SELECT `sn` FROM `users` WHERE `user_id` LIKE '".$row['user_id_from']."'";
	$snFrom = mysql_fetch_assoc(mysql_query($sql, $link));
	$jFrom = array_search($snFrom['sn'], $nodes);
	//If new user, add to verticies and increment index
	if(!$jFrom){
		$nodes[$j] = $snFrom['sn'];
		$jFrom = $j;
		$j++;
	}
	
	$jTo = array_search($row['user_sn_to'], $nodes);
	if(!$jTo){
		$nodes[$j] = $row['user_sn_to'];
		$jTo = $j;
		$j++;	
	}
	
	$arcs[$i]['from'] = $jFrom;
	$arcs[$i]['to'] = $jTo;
	$arcs[$i]['weight'] = $row['weight'];
	$i++;
}
mysql_free_result($result);

//Locations
for($j = 0; $j<count($nodes); $j++){
	$sql = "SELECT `city`, `country` FROM `users` WHERE `sn` LIKE '".$nodes[$j]."'";
	$result = mysql_query($sql, $link);

	while($row = mysql_fetch_assoc($result)){
		$locs[$j]['country'] = getCountry($row['country']);
		$locs[$j]['city'] = getCity($row['city'], $row['country']);
		break;
	}
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
for($i = 1; $i <= $numArcs; $i++){
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

$net = 'Pajek/'.$file.'.net';
$handle = fopen($net, 'w') or die('Failed to open file');
fwrite($handle, $data);
fclose($handle);


//Create city .clu data string
$data = '*Partition Location_by_city'.$nl;
$data.= '*Vertices '.$numV.$nl;
for($i = 1; $i <= $numV; $i++){
	$data.= $locs[$i]['city'].$nl;
}


$cityClu = 'Pajek/'.$file.'City.clu';
$handle = fopen($cityClu, 'w') or die('Failed to open file');
fwrite($handle, $data);
fclose($handle);


//Create country .clu data string
$data = '*Partition Location_by_country'.$nl;
$data.= '*Vertices '.$numV.$nl;
for($i = 1; $i <= $numV; $i++){
	$data.= $locs[$i]['country'].$nl;
}

$countryClu = 'Pajek/'.$file.'Country.clu';
$handle = fopen($countryClu, 'w') or die('Failed to open file');
fwrite($handle, $data);
fclose($handle);


echo "<b>Complete <br> See files:</b> $file <br>";


////
mysql_close($link);
///////////////////////////////////////////////////////////////////////////




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
	$sql = "SELECT `numcode` FROM `COUNTRIES` WHERE `iso` LIKE '$iso'";
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
		//echo "$city, $iso => 0<br>";
		return 0;
	}
	else{
		$sql = "SELECT `ID` FROM `CITIES` WHERE (`Name` LIKE '%$city%' AND `iso` LIKE '$iso')";
	
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