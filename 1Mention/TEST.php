<?php

print_r(searchFile('abidif=actor'));





//Returns $authHub, an array that stores a binary value for auth and hub
function searchFile($sn){
	$authHub['auth'] = 0;
	$authHub['hub'] = 0;
	
	$A = file_get_contents('AH/A.txt');
	if(strpos($A, $sn) !== false)
		$authHub['auth'] = 1;
	fclose($handleA);
	
	
	
	$H = file_get_contents('AH/H.txt');
	if(strpos($H, $sn) !== false)
		$authHub['hub'] = 3;
	fclose($handleH);
	
	return $authHub;
}

?>
