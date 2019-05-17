<?php

require("../phpMQTT.php");


$server = "mqtt.example.com";     // change if necessary
$port = 1883;                     // change if necessary
$username = "";                   // set your username
$password = "";                   // set your password
$client_id = "phpMQTT-subscriber"; // make sure this is unique for connecting to sever - you could use uniqid()

$mqtt = new phpMQTT($server, $port, $client_id);

$mqtt->connect(true, NULL, $username, $password);
$topic = 'yourtopic';
$msg = $mqtt->subscribe($topic, 0);
$mqtt->close();