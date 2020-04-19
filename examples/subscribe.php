<?php

require('../phpMQTT.php');


$server = 'localhost';     // change if necessary
$port = 1883;                     // change if necessary
$username = '';                   // set your username
$password = '';                   // set your password
$client_id = 'phpMQTT-subscriber'; // make sure this is unique for connecting to sever - you could use uniqid()

$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);
if(!$mqtt->connect(true, NULL, $username, $password)) {
	exit(1);
}

$mqtt->debug = true;

$topics['bluerhinos/phpMQTT/examples/publishtest'] = array('qos' => 0, 'function' => 'procMsg');
$mqtt->subscribe($topics, 0);

while($mqtt->proc()) {

}

$mqtt->close();

function procMsg($topic, $msg){
		echo 'Msg Recieved: ' . date('r') . "\n";
		echo "Topic: {$topic}\n\n";
		echo "\t$msg\n\n";
}
