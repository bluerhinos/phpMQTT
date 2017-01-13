<?php

require("../phpMQTT.php");


  $server = "m11.cloudmqtt.com";    // change if necessary
    $port = 13251;                  // change if necessary
$username = "";                     // set your username
$password = "";                     // set your password

$mqtt = new phpMQTT($server, $port, "phpMQTT-publisher");

if ($mqtt->connect(true, NULL, $username, $password)) {
	$mqtt->publish("bluerhinos/phpMQTT/examples/publishtest","Hello World! at ".date("r"),0);
	$mqtt->close();
} else {
    echo "Time out!";
}

?>
