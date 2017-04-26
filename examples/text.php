<?php

require '../vendor/autoload.php';

use phpMQTT\Topic;

$route = '/users/+id/blah/2/#';

$obj = new Topic($route, 0, function ($topic, $response) {
	
});

var_dump($obj);
