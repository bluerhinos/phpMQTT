<?php

require("../phpMQTT.php");

	
$mqtt = new phpMQTT("example.com", 1883, "PHP MQTT Client");

if(!$mqtt->connect()){
	exit(1);
}

$topics['ferries/IOW/#'] = array("qos"=>0, "function"=>"procmsg");
$mqtt->subscribe($topics,0);

while($mqtt->proc()){
		
}


$mqtt->close();

function procmsg($topic,$msg){
		echo "Msg Recieved: ".date("r")."\nTopic:{$topic}\n$msg\n";
}
	


?>
