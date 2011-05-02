<?php
const MY_SQL_SERVER = 'research.bowdoin.edu';
const MY_SQL_USER = 'ruivoss';
const MY_SQL_PWD = 'rtwitps1';

$sqlData = array();

$link = mysql_connect(MY_SQL_SERVER, MY_SQL_USER, MY_SQL_PWD) or die(mysql_error());
mysql_select_db('SEEDS') or die(mysql_error());
////////////////////////////////////////////////////////////////////////////////
//Input
$subset = $_POST['subset'];
if($subset != '')
	$subset =  ' AND '.$subset;
// //


$sql = "SELECT `screen_name`, `name`, `created_at`, `lat`, `long` FROM `$table` WHERE `latitude` != 0 $subset";
$result = mysql_query($sql, $link);

while($row = mysql_fetch_assoc($result))
	array_push($sqlData, json_encode($row));
	
mysql_free_result($result);

////
mysql_close($link);
?>



<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> Tweet Location Visualization </TITLE>
<META NAME="Generator" CONTENT="EditPlus">

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false">
</script>
<script type="text/javascript">
function initialize(){


//MAP stuff
var centerLatLng = new google.maps.LatLng(37.037778, -95.626389);
var opt ={ 
center:centerLatLng,
zoom:4,
mapTypeId: google.maps.MapTypeId.ROADMAP,
disableAutoPan:false,
navigationControl:true,
navigationControlOptions: {style:google.maps.NavigationControlStyle.SMALL },
mapTypeControl:true,
mapTypeControlOptions: {style:google.maps.MapTypeControlStyle.DROPDOWN_MENU}};
var map = new google.maps.Map(document.getElementById("map"),opt);

//Marker stuff
var sql = <?php echo json_encode($sqlData);?>;

for(var i = 0; i < sql.length; i++){
	
	var lat = eval("(" +sql[i]+ ')')["latitude"];
	var lng = eval("(" +sql[i]+ ')')["longitude"];
	
	var marker = new google.maps.Marker({
	position: new google.maps.LatLng(lat,lng),
	clickable: true,
	map: map});

	attachInfo(marker, i);


	
	
	
}

function attachInfo(marker, i){
	var text = eval("(" +sql[i]+ ')')["text"];
	var user = eval("(" +sql[i]+ ')')["user_sn"];
	var date = eval("(" +sql[i]+ ')')["date"];
	var source = eval("(" +sql[i]+ ')')["source"];
	var windowText = '<h1>'+user+'</h1>'+'<p><b>'+date+': </b>'+text+'</p>'+'<p>Source: '+source+'</p>';
	


	var window = new google.maps.InfoWindow({
		content: windowText,
		disableAutoPan: true});
	google.maps.event.addListener(marker,'mouseover',function(){
		window.open(map,marker);});
		google.maps.event.addListener(marker,'mouseout',function(){
		window.close(map,marker);});
}

}

</script>
<style type"text/css">
#map{
width:100%;
height:88%;
float:left;


}
</style>
</HEAD>

<BODY onload="initialize();">
<div><h1>Tweets Map</h1>
<h2>Location data is from a tweet's geotag info (e.g. IP address, GPS coordinates, etc.)</h2></div>

<div id="map" ></div>

</BODY>
</HTML>