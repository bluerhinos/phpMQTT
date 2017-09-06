<?php

require dirname(__FILE__)."/../vendor/autoload.php";

$host = "iot.eclipse.org";
$port = 1883;
$clientID = md5(uniqid()); // use a unique client id for each connection
$username = ''; // username is optional
$password = ''; // password is optional

$mqtt = new \Lightning\App($host, $port, $clientID, $username, $password);

if (!$mqtt->connect()) {
	echo "Failed to connect.\n";
}

$mqtt->publish("test/user/1/status", '{"hello":"world"}', 1);
$mqtt->publish("test/request/hello/world", '{"hello":"world"}', 1);
$mqtt->close();

?>
