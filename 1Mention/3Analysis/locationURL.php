<?php
$mysql_server = 'research.bowdoin.edu';
$mysql_user = 'ruivoss';
$mysql_pwd = 'rtwitps1';

//mySQL link
$db = 'twitter';
$table = 'urls';


$link = mysql_connect($mysql_server, $mysql_user, $mysql_pwd) or die(mysql_error());
mysql_query("SET NAMES 'utf8'");
mysql_select_db($db) or die(mysql_error());

$sql = "SELECT `url`,`tweet_id` FROM `$table` ORDER BY `$table`.`url` ASC";
$result = mysql_query($sql, $link);

///////////////////////////////////
//LocURL STATS//

$filename = 'URL/LocURL.txt';

$handle = fopen($filename, 'w') or die('Failed to open file');

$nl = "\n";
$tab = "\t";
$url = '';
$count = 0;
while($row = mysql_fetch_assoc($result)){
	$tweet_id = $row['tweet_id'];
	$url = $row['url'];
	$domain = getDomain($row['url']);
	
	//Lookup tweet to get user
	mysql_select_db('networks') or die(mysql_error());
	$sql2 = "SELECT `user_id` FROM `tweets` WHERE `tweet_id`=$tweet_id";
	$result2 = mysql_query($sql2, $link);
	while($row = mysql_fetch_assoc($result2)){
		$user_id = $row['user_id'];
		
		//Lookup user to get location stuff
		$sql3 = "SELECT `city`,`country` FROM `users` WHERE `user_id`=$user_id";
		$result3 = mysql_query($sql3, $link);
		while($row = mysql_fetch_assoc($result3)){
			$city = $row['city'];
			$country = $row['country'];
			break;
		}
	}	
	fwrite($handle, $url.$tab.$domain.$tab.$city.$tab.$country.$nl);
}

fclose($handle);

///////////////////////////////////
//DOMAIN STATS//









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
