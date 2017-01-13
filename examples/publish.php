<?php

require("../phpMQTT.php");


$mqtt = new phpMQTT("m11.cloudmqtt.com", 13251, "ClientID".rand());

if ($mqtt->connect(true, NULL, "USERNAME_HERE", "PASSWORD_HERE")) {
	$mqtt->publish("bluerhinos/phpMQTT/examples/publishtest","Hello World! at ".date("r"),0);
	$mqtt->close();
}

?>
