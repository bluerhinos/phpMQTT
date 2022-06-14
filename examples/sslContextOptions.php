<?php

require('../phpMQTT.php');

$server = 'localhost';              // change if necessary
$port = 8883;                       // change if necessary
$username = '';                     // set your username
$password = '';                     // set your password
$client_id = 'phpMQTT-publisher';   // make sure this is unique for connecting to sever - you could use uniqid()

$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

// This can be used if the server needs an encrypted connection, but you can not verify the
// server (because you don't have the certificate) or you just don't need to verify it.
// See https://www.php.net/manual/en/context.ssl.php for further options.
$mqtt->setSslContextOptions(
    [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]
);

if ($mqtt->connect(true, NULL, $username, $password)) {
	$mqtt->publish('bluerhinos/phpMQTT/examples/sslContextOptionsTest', 'Hello World! at ' . date('r'), 0, false);
	$mqtt->close();
} else {
    echo "Time out!\n";
}

?>
