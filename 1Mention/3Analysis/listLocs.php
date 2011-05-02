<?php
$mysql_server = 'research.bowdoin.edu';
$mysql_user = 'ruivoss';
$mysql_pwd = 'rtwitps1';

//mySQL link
$db = 'networks';
$table = $_POST['table'];
$subset = $_POST['subset'];
if($subset != '')
	$subset =  " WHERE $subset";

$link = mysql_connect($mysql_server, $mysql_user, $mysql_pwd) or die(mysql_error());
mysql_query("SET NAMES 'utf8'");
mysql_select_db($db) or die(mysql_error());

///////////////////////////////////
//City List//
$sql = "SELECT `city` FROM `$table` $subset ORDER BY `$table`.`city` ASC";
$result = mysql_query($sql, $link);

$filename = 'Locations/Cities.txt';

$handle = fopen($filename, 'w') or die('Failed to open file');

$nl = "\n";
$tab = "\t";
$city = '';
$count = 0;
while($row = mysql_fetch_assoc($result)){
	if($count==0){
		fwrite($handle, $row['city']);
		$count++;
		$city = $row['city'];
	}
	else if($row['city']==$city)
		$count++;
	else{
		fwrite($handle, $tab.$count.$nl);
		$count = 0;
		fwrite($handle, $row['city']);
		$count++;
		$city = $row['city'];
	}	
}
fwrite($handle, $tab.$count.$nl);
fclose($handle);
mysql_free_result($result);
///////////////////////////////////
//Countries List
$sql = "SELECT `country` FROM `$table` $subset ORDER BY `$table`.`country` ASC";
$result = mysql_query($sql, $link);

$filename = 'Locations/Countries.txt';

$handle = fopen($filename, 'w') or die('Failed to open file');

$nl = "\n";
$tab = "\t";
$country = '';
$count = 0;
while($row = mysql_fetch_assoc($result)){
	if($count==0){
		fwrite($handle, $row['country']);
		$count++;
		$country = $row['country'];
	}
	else if($row['country']==$country)
		$count++;
	else{
		fwrite($handle, $tab.$count.$nl);
		$count = 0;
		fwrite($handle, $row['country']);
		$count++;
		$country = $row['country'];
	}	
}
fwrite($handle, $tab.$count.$nl);
fclose($handle);
mysql_free_result($result);

////
mysql_free_result($result);
mysql_close($link);
?>