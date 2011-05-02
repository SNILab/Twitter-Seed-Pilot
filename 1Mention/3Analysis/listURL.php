<?php
$mysql_server = 'research.bowdoin.edu';
$mysql_user = 'ruivoss';
$mysql_pwd = 'rtwitps1';

//mySQL link
$db = 'twitter';
$table = $_POST['table'];
$subset = $_POST['subset'];
if($subset != '')
	$subset =  " WHERE $subset";

$link = mysql_connect($mysql_server, $mysql_user, $mysql_pwd) or die(mysql_error());
mysql_query("SET NAMES 'utf8'");
mysql_select_db($db) or die(mysql_error());

$sql = "SELECT `url` FROM `$table` $subset ORDER BY `$table`.`url` ASC";
$result = mysql_query($sql, $link);

///////////////////////////////////
//URL STATS//

$filename = 'URL/URL.txt';

$handle = fopen($filename, 'w') or die('Failed to open file');

$nl = "\n";
$tab = "\t";
$url = '';
$count = 0;
while($row = mysql_fetch_assoc($result)){
	if($count==0){
		fwrite($handle, $row['url']);
		$count++;
		$url = $row['url'];
	}
	else if($row['url']==$url)
		$count++;
	else{
		fwrite($handle, $tab.$count.$nl);
		$count = 0;
		fwrite($handle, $row['url']);
		$count++;
		$url = $row['url'];
	}	
}
fwrite($handle, $tab.$count.$nl);
fclose($handle);

///////////////////////////////////
//DOMAIN STATS//
$result = mysql_query($sql, $link);
$filename = 'URL/DOMAIN.txt';

$handle = fopen($filename, 'w') or die('Failed to open file');

$nl = "\n";
$tab = "\t";
$domain = '';
$count = 0;
while($row = mysql_fetch_assoc($result)){
	if($count==0){
		$domain = getDomain($row['url']);
		fwrite($handle, $domain);
		$count++;
		
	}
	else if(getDomain($row['url'])==$domain)
		$count++;
	else{
		fwrite($handle, $tab.$count.$nl);
		$count = 0;
		$domain = getDomain($row['url']);
		fwrite($handle, $domain);
		$count++;
	}	
}
fwrite($handle, $tab.$count.$nl);
fclose($handle);


function getDomain($url){
	$slash = strpos($url, '/');
	if($slash !== false)
		$url = substr($url, 0, $slash);
	
	$colon = strpos($url, ':');
	if($colon !== false)
		$url = substr($url, 0, $colon);
	
	$quest = strpos($url, '?');
	if($quest !== false)
		$url = substr($url, 0, $quest);
	
	return $url;
}	



////
mysql_free_result($result);
mysql_close($link);
?>