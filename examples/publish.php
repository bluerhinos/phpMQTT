<?php

require("../phpMQTT.php");

	
$mqtt = new phpMQTT("example.com", 1883, "phpMQTT Pub Example"); //Change client name to something unique

if ($mqtt->connect()) {
	$mqtt->publish("bluerhinos/phpMQTT/examples/publishtest","Hello World! at ".date("r"),0);
	$mqtt->close();
}

?>
