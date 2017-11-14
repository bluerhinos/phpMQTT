<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 13/11/2017
 * Time: 23:21
 */


function dd(...$args)
{
    var_dump(...$args);
    die;
}

require_once "vendor/autoload.php";


$socket = new \PhpMqqt\Mqtt\Socket\Socket();
$publish = new \PhpMqqt\Mqtt\Publish($socket, '12345');

$publish->publish('test/test', 'Messaj de test');
//return;
$x = new \PhpMqqt\PhpMqqt();

//require_once "phpMQTT.php";

//$x = new \Bluerhinos\phpMQTT('127.0.0.1',1883,'unique');

$x->connect(true/*, [
    'topic' => 'test/test',
    'content' => "Will Message",
    'qos' => 1,
    'retain' => 1

]*/);

$x->publish('test/x', "message " . time());
//
//die;

//$tops = [
//    'test/test' => [
//        'qos' => 0,
//        'function' => function ($topic, $msg) {
//            echo PHP_EOL;
//            var_dump($topic);
//            echo PHP_EOL;
//            var_dump($msg);
//            echo PHP_EOL;
//            echo PHP_EOL;
//        }
//    ]
//];
//
//$x->subscribe($tops,0);
//
//while ($x->proc()) {
//
//}


echo 'Here';

