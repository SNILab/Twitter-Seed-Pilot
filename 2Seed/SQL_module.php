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




////////////////////////////////////////////////////////////////////////////////
//MAIN//////////////////////////////////////////////////////////////////////////
	//Adds unique index to 'id' field
$sql = "ALTER TABLE `Temp` ADD UNIQUE(`id`)";
mysql_query($sql);




////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

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
function dbRead($table, $where){
	$where = escapeArray($where);
	$where = updateFormatArray($where, 'WHERE');
	$format = "SELECT * FROM %s WHERE %s";
	
	$sql = sprintf($format, $table, $where);

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