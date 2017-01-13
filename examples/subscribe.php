<?php

require("../phpMQTT.php");


  $server = "m11.cloudmqtt.com";    // change if necessary
    $port = 13251;                  // change if necessary
$username = "";                     // set your username
$password = "";                     // set your password

$mqtt = new phpMQTT($server, $port, "phpMQTT-subscriber");

if(!$mqtt->connect(true, NULL, $username, $password)) {
	exit(1);
}

$topics['bluerhinos/phpMQTT/examples/publishtest'] = array("qos" => 0, "function" => "procmsg");
$mqtt->subscribe($topics, 0);

while($mqtt->proc()){
		
}


$mqtt->close();

function procmsg($topic, $msg){
		echo "Msg Recieved: " . date("r") . "\n";
		echo "Topic: {$topic}\n\n";
		echo "\t$msg\n\n";
}


?>
