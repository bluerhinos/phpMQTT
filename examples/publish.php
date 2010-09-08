<?php

require("../phpMQTT.php");

	
$mqtt = new phpMQTT();
$mqtt->broker("example.com", 1883, "PHP MQTT Client");
$mqtt->connect();
$mqtt->publish("bluerhinos/phpMQTT/examples/publishtest","Hello World! at ".date("r"),0);
$mqtt->close();

?>