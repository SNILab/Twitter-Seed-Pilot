
<?php
//Takes parameters from web form and initiates a command line run with the parameters
////
echo 'Start:';
$track = escapeshellarg($_POST['track']);
$time = $_POST['time'];

shell_exec("php /Users/slongwel/Desktop/TwitterProject/1Collection/Phirehose.php $track $time");
echo "Track: $track";
?>