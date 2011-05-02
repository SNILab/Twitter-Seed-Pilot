<?php
const MY_SQL_SERVER = 'localhost';
const MY_SQL_USER = 'root';
const MY_SQL_PWD = 'root';

//mySQL link
$db = 'twitter';
$table = 'CityMod';
$link = mysql_connect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_set_charset('utf8', $link);
mysql_select_db($db) or die(mysql_error());


$sql = "SELECT `ID`, `CountryCode` FROM `CityMod`";
$result = mysql_query($sql, $link);

$k = 0;
while($row = mysql_fetch_assoc($result)){
	$iso3 = $row['CountryCode'];
	echo $iso3.'<br>';


	$sql = "SELECT `iso` FROM `country` WHERE `iso3` ='$iso3'";
	$result2 = mysql_query($sql, $link);
	$iso2 = mysql_result($result2, 0);	
	echo $iso2.'<br><br>';	
	
	$sql = "UPDATE `CityMod` SET `iso` = '".$iso2."' WHERE `id` ='".$row['ID']."'";
	mysql_query($sql, $link);


}
	

mysql_free_result($result);
mysql_close($link);
?>