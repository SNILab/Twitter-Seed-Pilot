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
		$userC = mysql_query("SELECT COUNT(1) AS `userCount` FROM Users",$link);
		$userD = mysql_fetch_assoc($userC);
		$userCount = $userD['userCount'];		
		print("Total Users to Process: {$userCount}<br/>\n");		
		$userCount100 = $userCount/100;
		$u=$p=$l=0;
		
		mysql_free_result($userC);
		//Cycle Through User Resources for Processing
		//In Blocks of 100,000
		$userCursor    = 0;
		$userBlockSize = 100;
		
		for($userCursor=0; $userCursor < $userCount&&$u==0; $userCursor+=$userBlockSize) {
			$userBlock = mysql_query("SELECT `id` AS `uid` FROM Users LIMIT {$userCursor},{$userBlockSize}",$link);
			//print("Block: ".$userCursor." : ".($userCursor+$userBlockSize)."<br/>\n");			
			while(($thisUser = mysql_fetch_assoc($userBlock)) && $u==0) {
				//Get Distinct Reciprocal Arcs for each user
			  	print_r($thisUser);
	       		print(
					"SELECT 
	       				CASE WHEN id_from < id_to THEN id_from ELSE id_to END AS idFrom, 
	       				CASE WHEN id_from < id_to THEN id_to ELSE id_from END AS idTo 
	       			FROM ( 
	       				SELECT `id_from',`id_to` 
	       				FROM Arcs 
	       				WHERE `id_from`={$thisUser['uid']} OR `id_to`={$thisUser['uid']} 
	       				) AS Arcs531 
	       			GROUP BY idFrom,idTo HAVING COUNT(*)=2"
	       		);
	       		
	       		
				$userArcData = mysql_query(
					"SELECT 
	       				CASE WHEN id_from < id_to THEN id_from ELSE id_to END AS idFrom, 
	       				CASE WHEN id_from < id_to THEN id_to ELSE id_from END AS idTo 
	       			FROM ( 
	       				SELECT `id_from`,`id_to` 
	       				FROM Arcs 
	       				WHERE `id_from`={$thisUser['uid']} OR `id_to`={$thisUser['uid']} 
	       				) AS Arcs531 
	       			GROUP BY idFrom,idTo HAVING COUNT(*)=2"
	       		);
				
				while($arc = mysql_fetch_assoc($userArcData)){
					print("*");
					$aFrom = $arc['idFrom'];
					$aTo   = $arc['idTo'];
					
					//Test if peer link exists			
					$peerExists = mysql_query(
						"SELECT IF (
							EXISTS ( SELECT * FROM Peers WHERE id_from={$aFrom} AND id_to={$aTo}), 
							1, 0
						) AS peerExists"
					,$link);
					$peerExistsData = mysql_fetch_assoc($peerExists);
				
					//If there is no peer entry in Peers
					//Then test to see if Arc is a reciprical link
					
					if($peerExistsData['peerExists'] == 1) {
						print("{$aFrom}\tpeers\t{$aTo}<br/>\n");
						fwrite($sifFile,"{$from}\tpeers\t{$to}\n");
						$p++;
					} else {
						$l++;
					}
				}

			  
				//print("User: {$u}: ".$thisUser['uid']."<br/>\n");
				$u++;
			}
			print("After {$u} ".($u/$userCount100)."% completed<br/>\n");
		}
		
		/*
		$resultts = mysql_query("SELECT `id_from`,`id_to` FROM Arcs",$link);
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
		*/
		
		print("<br/>\nMade File<br/>\n");
		print("Peers:\t{$p}<br/>\nLinks:\t{$l}<br/>\n");
	} else {
		print("nothing comes<br/>\n");
		//header("Location:export.html");
	}
?>