<?php

require("../phpMQTT.php");


$mqtt = new phpMQTT("m11.cloudmqtt.com", 13251, "ClientID".rand());

if(!$mqtt->connect(true, NULL, "USERNAME_HERE", "PASSWORD_HERE")){
	exit(1);
}

$topics['bluerhinos/phpMQTT/examples/publishtest'] = array("qos"=>0, "function"=>"procmsg");
$mqtt->subscribe($topics,0);

while($mqtt->proc()){
		
}


$mqtt->close();

function procmsg($topic,$msg){
		echo "Msg Recieved: ".date("r")."\nTopic:{$topic}\n$msg\n";
}


?>
