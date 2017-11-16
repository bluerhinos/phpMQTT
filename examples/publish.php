<?php


require_once __DIR__ . "/../vendor/autoload.php";

$client_id = "phpMQTT-publisher"; // make sure this is unique for connecting to sever - you could use uniqid()

$socket = new \PhpMqqt\Mqtt\Socket\Socket();

$publish = new \PhpMqqt\Mqtt\Publish\Publish($socket, $client_id);

$publish->publish(new \PhpMqqt\Mqtt\Publish\Topic('test'), 'Some random message' . uniqid())
    ->publish(new \PhpMqqt\Mqtt\Publish\Topic('test/test'), 'Some another random message' . uniqid());


$publish->close();