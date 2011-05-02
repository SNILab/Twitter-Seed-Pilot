<?php
	$nl = "\n";

	$handle = fopen('AH/lookup.net', 'r') or die('Failed to open file');
	$handleAuths = fopen('AH/auths.clu', 'r') or die('Failed to open file');
	$handleHubs = fopen('AH/hubs.clu', 'r') or die('Failed to open file');
	$handleA = fopen('AH/A.txt', 'w') or die('Failed to open file');
	$handleH = fopen('AH/H.txt', 'w') or die('Failed to open file');
	
	while($line = fgets($handle)){
		$lineAuths =  fgets($handleAuths);
		$lineHubs =  fgets($handleHubs);
		
		$start = strpos($line,'"')+1;
		$length = strpos($line,'"',$start)-$start;

		if($lineHubs == 3){
			fwrite($handleH, substr($line, $start, $length).$nl);
		}
		if($lineAuths == 1){
			fwrite($handleA, substr($line,$start,$length).$nl);
		}
	}
	
	fclose($handle);
	fclose($handleAuths);
	fclose($handleHubs);
	fclose($handleA);
	fclose($handleH);
	



?>
