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
$netFilename = 'TEST.net';
//     //



//Create 'Temp' table
$sql = "CREATE TABLE Temp SELECT `id`, `screen_name` FROM Users";
mysql_query($sql);
	
//Numbers 'Temp' with primary key
$sql = "ALTER TABLE Temp ADD `#` INT UNSIGNED NOT NULL auto_increment PRIMARY KEY FIRST";
mysql_query($sql);
	
//Adds unique index to 'id' field
$sql = "ALTER TABLE Temp ADD UNIQUE (id)";
mysql_query($sql);



//File writing:
$nl = "\r\n";
$handle = fopen($netFilename, 'w');
flock($handle, LOCK_EX);

//Verticies
fwrite($handle, '*Verticies ');
$sql = "SELECT * FROM Temp";
$result = mysql_query($sql);
$numVert = mysql_num_rows($result);
fwrite($handle, $numVert.$nl);
while($row = mysql_fetch_assoc($result)){
	echo $row['#'].' '.$row['id'].$nl;
	//fwrite($handle, $row['#'].' '.$row['id'].$nl);
}
mysql_free_result($result);


//Arcs
fwrite($handle, '*Arcs'.$nl);
$result = dbRead('Arcs');
while($row = mysql_fetch_assoc($result)){
	$idFrom = $row['id_from'];
	$idTo = $row['id_to'];
	
	
	$sql = "SELECT `#` FROM Temp WHERE id='$idFrom'";
	$result2 = mysql_query($sql);
	$row2 = mysql_fetch_assoc($result2);
	mysql_free_result($result2);
	if($row2 === false)
		continue;
	$from = $row2['#'];

		
	$sql = "SELECT `#` FROM Temp WHERE id='$idTo'";
	$result2 = mysql_query($sql);
	$row2 = mysql_fetch_assoc($result2);
	mysql_free_result($result2);
	if($row2 === false)
		continue;
	$to = $row2['#'];

	fwrite($handle, $from. ' '.$to.$nl);
}
mysql_free_result($result);







//
fclose($handle);
delTable('Temp');
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