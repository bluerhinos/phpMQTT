<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 13/11/2017
 * Time: 23:21
 */


//require_once "vendor/autoload.php";

//$x = new \PhpMqqt\PhpMqqt\PhpMqqt();

require_once "phpMQTT.php";

$x = new \Bluerhinos\phpMQTT('127.0.0.1',1883,'unique');

$x->connect(true, null);

$tops = [
    'test' => [
        'qos' => 0,
        'function' => function ($topic, $msg) {
            echo PHP_EOL;
            var_dump($topic);
            echo PHP_EOL;
            var_dump($msg);
            echo PHP_EOL;
            echo PHP_EOL;
        }
    ]
];

$x->subscribe($tops,0);

while ($x->proc()) {

}


echo 'Here';

