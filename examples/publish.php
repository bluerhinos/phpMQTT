<?php

require('../phpMQTT.php');

$server = 'localhost';     // change if necessary
$port = 1883;                     // change if necessary
$username = '';                   // set your username
$password = '';                   // set your password
$client_id = 'phpMQTT-publisher'; // make sure this is unique for connecting to sever - you could use uniqid()

$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

if ($mqtt->connect(true, NULL, $username, $password)) {
	$mqtt->publish('bluerhinos/phpMQTT/examples/publishtest', 'Hello World! at ' . date('r'), 0, false);
	$mqtt->close();
} else {
    echo "Time out!\n";
}
