<?php

require("../phpMQTT.php");

$server = "mqtt.example.com";     // change if necessary
$port = 1883;                     // change if necessary
$username = "";                   // set your username
$password = "";                   // set your password
$client_id = "phpMQTT-publisher"; // make sure this is unique for connecting to sever - you could use uniqid()
$topic = 'yourtopic';
$mqtt = new phpMQTT($server, $port, $client_id);
if ($mqtt->connect(true, NULL, $username, $password)) {
    $mqtt->publish($topic,  $content);
    $mqtt->close();
} else {
    //improve this
    echo "Time out!\n";
}