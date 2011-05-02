<?php
//Constants
$my_sql_server = 'localhost';
$my_sql_user = 'root';
$my_sql_pwd = 'root';

//mySQL setup
$db = 'SEEDS';
$link = mysql_connect($my_sql_server, $my_sql_user, $my_sql_pwd) or die(mysql_error());
mysql_set_charset('utf8', $link);
mysql_select_db($db) or die(mysql_error());


////MAIN////////////////////////////////////////////////////////////////////////////
//Input//
$path = '/Users/slongwel/Desktop/';
$netFilename = 'TEST.net';
$where = " WHERE 1";
//     //
$filepath = $path.$netFilename;

//Create 'TempUsers' table
$sql = "CREATE TABLE TempUsers SELECT `id`, `screen_name` FROM Users".$where;
mysql_query($sql);


//Get count of 'TempUsers'
$sql = "SELECT COUNT(*) FROM TempUsers";
$result = mysql_query($sql);
$numVerticies = mysql_result($result, 0);
mysql_free_result($result);

//Numbers 'TempUsers' with primary key
$sql = "ALTER TABLE TempUsers ADD `#` INT UNSIGNED NOT NULL auto_increment PRIMARY KEY FIRST";
mysql_query($sql);
	
//Adds unique index to 'id' field
$sql = "ALTER TABLE TempUsers ADD UNIQUE (id)";
mysql_query($sql);

//Create 'TempArcs' table
$sql = "CREATE TABLE TempArcs LIKE Arcs";
mysql_query($sql);






$result = dbRead('Arcs');
while($row = mysql_fetch_assoc($result)){
	$idFrom = $row['id_from'];
	$idTo = $row['id_to'];
	
	//Lookup $idFrom in TempUsers
	$sql = "SELECT `#` FROM TempUsers WHERE id='$idFrom'";
	$result2 = mysql_query($sql);
	if(mysql_num_rows($result2) != 1){
		mysql_free_result($result2);
		continue;
	}
	$row2 = mysql_fetch_assoc($result2);
	mysql_free_result($result2);

	$input['id_from'] = $row2['#'];

	//Lookup $idTo
	$sql = "SELECT `#` FROM TempUsers WHERE id='$idTo'";
	$result2 = mysql_query($sql);
	if(mysql_num_rows($result2) != 1){
		mysql_free_result($result2);
		continue;
	}
	$row2 = mysql_fetch_assoc($result2);
	mysql_free_result($result2);

	$input['id_to'] = $row2['#'];
	
	dbWrite('TempArcs', $input);
}
mysql_free_result($result);










//File Create
$sql = "SELECT `#`,`id` FROM TempUsers INTO OUTFILE '/Users/slongwel/Desktop/Users.txt' FIELDS TERMINATED BY ' ' LINES TERMINATED BY '\r\n'";
mysql_query($sql);

$sql = "SELECT * FROM TempArcs INTO OUTFILE '/Users/slongwel/Desktop/Arcs.txt' FIELDS TERMINATED BY ' ' LINES TERMINATED BY '\r\n'";
mysql_query($sql);

$nl = "\r\n";
$finalH = fopen('/Users/slongwel/Desktop/TEST.net', 'a');

fwrite($finalH, '*Vertices '.$numVerticies.$nl);
$contents = file_get_contents('/Users/slongwel/Desktop/Users.txt');
fwrite($finalH, $contents);
fwrite($finalH, '*Arcs '.$nl);
$contents = file_get_contents('/Users/slongwel/Desktop/Arcs.txt');
fwrite($finalH, $contents);

fclose($finalH);


//
delTable('TempUsers');
delTable('TempArcs');
mysql_close();
//FUNCTIONS/////////////////////////////////////////////////////////////////////////////////
//Deletes the specified MySQL table: CAREFUL!!!!!
function delTable($table){
	if($table == 'Users' || $table == 'Arcs')
		echo "You don't really want to delete that!";
	else{
		$table  = mysql_real_escape_string($table);
		$sql = "DROP TABLE $table";
		mysql_query($sql);
	}
}














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
function dbRead($table){
	$format = "SELECT * FROM %s";
	$sql = sprintf($format, $table);

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




?>