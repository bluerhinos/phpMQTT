<?php

require("../vendor/autoload.php");

/**
 * An example callback function that is not inline.
 * @param  phpMQTT\Response $response
 */
function callbackFunction($response) {
	$topic = $response->getRoute();
	$wildcard = $response->getWildcard();
	$message = $response->getMessage();

	echo "Message recieved:\n =============\n Topic: $topic\n Wildcard: $wildcard\n Message:\n $message\n\n";
}

$host = "iot.eclipse.org";
$port = 1883;
$clientID = md5(uniqid()); // use a unique client id for each connection
$username = ''; // Username is optional
$password = ''; // password is optional

$mqtt = new \phpMQTT\App($host, $port, $clientID, $username, $password);

// Optional debugging
$mqtt->debug(true);

if (!$mqtt->connect()) {
	echo "Failed to connect\n";
	exit(1);
}

// Add a new subscription for each topic that is needed
$mqtt->subscribe('test/user/+id/status', 0, function ($response) {
	$topic = $response->getRoute();
	$message = $response->getMessage();
	$attributes = $response->getAttributes(); // Returns all the attributes received
	$id = $response->attr('id'); // Gets a specific attribute by key. Returns null if not present.

	echo "Message recieved:\n =============\n Topic: $topic\n Attribute - id: $id\n Message:\n $message\n\n";
});

// Callback functions can be inline or by name as a string
$mqtt->subscribe('test/request/#', 0, 'callbackFunction');

// Call listen to begin polling for messages
$mqtt->listen();

?>
