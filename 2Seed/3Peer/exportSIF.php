<?php
	print("Ready to export<br>\n");
	if($_GET){
		//mySQL setup
		$db = 'SEEDS';
		$link = mysql_connect("localhost", "root", "root") or die(mysql_error());
		mysql_set_charset('utf8', $link);
		mysql_select_db($db) or die(mysql_error());
		
		//Output setup
		$sifFile = fopen("DrAnasYounesPeer.sif", "w");
		if(!$sifFile) die(print("Could not open file."));
		
		//Get Arc Count
		$arcC = mysql_query("SELECT COUNT(1) AS `arcCount` FROM Arcs",$link);
		$arcD = mysql_fetch_assoc($arcC);
		$arcCount = $arcD['arcCount'];
		print("Total Arcs to Process: ".$arcCount."<br/>\n");
		$arcCount/=100;
		mysql_free_result($arcC);
		
		//Get Arc Resource for Processing
		$results = mysql_query("SELECT `id_from`,`id_to` FROM Arcs",$link);
		if (!$results) {
    		die('Invalid query: ' . mysql_error());
		}
		
		$p = 0;
		$l = 0;
		$i = 0;
		while(($arc = mysql_fetch_assoc($results))&&true){
			
			if($arc['id_from'] < $arc['id_to']) {
				$from = $arc['id_from']; $to   = $arc['id_to'];
			} else {
				$from = $arc['id_to'];   $to   = $arc['id_from'];
			}
			
			//Test if peer link exists			
			$peerExists = mysql_query(
				"SELECT COUNT(1) AS `peerCount` ".
				"FROM Peers ".
				"WHERE `id_from`={$from} AND `id_to`={$to}"
			,$link);
			$peerExistsData = mysql_fetch_assoc($peerExists);
			
			//If there is no peer entry in Peers
			//Then test to see if Arc is a reciprical link
			if($peerExistsData['peerCount'] == 0) {
				$peerTest = mysql_query(
					"SELECT COUNT(1) AS `arcCount` ".
					"FROM Arcs ".
					"WHERE (`id_from`={$from} AND `id_to`={$to}) ".
					"OR    (`id_from`={$to} AND `id_to`={$from}) "
				,$link);
				$peerTestData = mysql_fetch_assoc($peerTest);
				
				//IF   Arc is reciprocal
				//THEN Insert into Peer table (lowest id first)
				//	   Write 'peers' link to SIF File and count peers link
				//ELSE Write 'links' link to SIF File and count links link
				if($peerTestData['arcCount'] == 2){
					mysql_query(
						"INSERT INTO Peers ".
						"VALUES ({$from},{$to},'')"
					,$link);
					fwrite($sifFile,"{$from}\tpeers\t{$to}\n");
					$p++;
				} else if ($peerTestData['arcCount'] == 1) {
					//fwrite($sifFile,$arc['id_from']."\tlinks\t".$arc['id_to']."\n");
					$l++;
				}
				//Free SQL resources
				mysql_free_result($peerTest);
			} else if ($peerExistsData['peerCount'] == 1) {
				//print("\n<br/>peer link {$from} to {$to} already found<br/>\n");
			}
			mysql_free_result($peerExists);
			if($i%1000==0)print(($i/$arcCount)."% completed<br/>\n");
			$i++;
		}
		mysql_free_result($results);
		
		print("<br/>\nMade File<br/>\n");
		print("Peers:\t{$p}<br/>\nLinks:\t{$l}<br/>\n");
	} else {
		print("nothing comes<br/>\n");
		//header("Location:export.html");
	}
?>