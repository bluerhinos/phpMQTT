#Lightning - MQTT Client

### Overview

Lightning is a PHP micro framework that lets you quickly connect, publish, and subscribe to MQTT topics. 
```php
<?php

require 'vendor/autoload.php';

$mqtt = new \Lightning\App();
$mqtt->subscribe('/hello/:name', 0, function ($response) {
    $topic = $response->getRoute();
    $name = $response->attr('name');
    $message = $response->getMessage();
});
$mqtt->listen();

$mqtt->close();
```

#### Features:
- PSR-4 compliant class loading
- Modular codebase for extensibility
- Topic router with inline variable support
- Wildcard support
- Lightweight MQTT Topic syntax checker

### Installation

#### System Requirements
- PHP 5.6 or newer

### How to Install Lightning
We recommend that you install Lightning with the [Composer](https://getcomposer.org) dependency manager, using:
```
composer install brandonhudson\lightning
```

Require the Composer autoloader into your PHP script, and you are ready to start using it.
```php
<?php
require 'vendor/autoload.php';
```

### Usage

#### Connection
In order to connect to an MQTT broker, you'll need to initialize a new Lighting\App object with the following parameters:

|Parameter   |Type    |Default |Description                                |
|:----------:|:------:|:------:|-------------------------------------------|
|**host**    |string  |(none)  |The MQTT broker you want to connect to     |
|**port**    |integer |(none)  |The port that the MQTT Broker is running on|
|**clientID**|string  |(none)  |The client ID for the connection you're establishing. This should be unique for each connection|
|**username**|string  |''      |The username for the broker. This is optional.|
|**password**|string  |''      |The password for the broker. This is optional.|

Example:
```php
<?php

require 'vendor/autoload.php';

$host = 'mqtt.example.com';
$port = 1883;
$clientID = md5(uniqid());
$username = 'user';
$password = 'password';

// Without username/password
$mqtt = new \Lightning\App($host, $port, $clientID);

// With username/password
$mqtt = new \Lightning\App($host, $port, $clientID, $username, $password);

if (!$mqtt->connect()) {
    exit(1);
}

$mqtt->close();

```

#### Publishing
To publish with Lightning, you'll need to have a connection to your MQTT broker already established. Publishing to a topic includes the following parameters:

|Parameter   |Type    |Default |Description                                |
|:----------:|:------:|:------:|-------------------------------------------|
|**topic**   |string  |(none)  |The topic to publish to                    |
|**message** |string  |(none)  |The message you want to publish            |
|**qos**     |integer |0       |The quality of service level you want for the published message. (0=at most once, 1=at least once, 2=exactly once). [Learn more](http://www.hivemq.com/blog/mqtt-essentials-part-6-mqtt-quality-of-service-levels)|

Example:
```php
<?php

require 'vendor/autoload.php';

$host = 'mqtt.example.com';
$port = 1883;
$clientID = md5(uniqid());

$mqtt = new \Lightning\App($host, $port, $clientID);

if (!$mqtt->connect()) {
    exit(1);
}

$mqtt->publish("/hello/world", '{"hello":"world"}', 1);
$mqtt->close();
```

#### Subscribing
##### Overview

Lightning offers you a powerful way to quickly subcribe and react to messages sent on specific topics. Modeled after the Slim PHP Framework, Lightning subscriptions are designed in a callback pattern, where a function is invoked when a message is recieved on the subscribed topic.

##### Topics
Lighting supports topics that use the `/` seperator for logical components of the topic. Example:
```
/home/temperature/increase
```
While this does narrow the possibilities for how you can structure your topics, we do this to allow you to parse variables out of the topic after it has been subscribed to (see below).

###### Inline Variables
Lighting supports inline variable declaration right inside of the topic, allowing you to easily parse variables out of the response when a message is received. In order to declare a variable, you include the `+` operator along with the variable name (ex: `+id`) in between two forward slashes.

Example:
```
/users/+id/status

/building/+id/zone/+zoneID/temperature
```

As with standard variable naming conventions, inline variables need to have unique names within a single topic (you cannot reuse the same variable name twice in a single topic). If you misformat a route, Lightning will throw a `Lightning\Exception\RouteException` detailing the problem.

###### Wildcards
Lightning also supports wildcard topic delcarlation, allowing you to recieve messages on a topic that does not have a fixed topic structure. Per the MQTT specification, wildcards are only available at the end of the topic - you cannot include a wildcard in the middle of a topic. A wildcard is denoted using the `#` symbol. Example:

```
/users/#  //subscribe to anything that has /users/ at the beginning of the topic
 
#  //subscribe to everything
```

If you misuse a wildcard in your topic, Lightning will throw a `Lightning\Exception\RouteException` detailing the problem.


###### Mixed Uses
Lighting topics can include both inline variables and wildcards in a topic. Example:

```
/users/+id/#

```

##### Callbacks
Lightning uses callback methods to invoke code when a message is received for a specific topic. A callback can be defined inline as an anonymous function when you subscribe to a topic, or you can pass the string name function that should be invoked - any function that will return `true` to `is_callable()` will work. 

The callback function takes one argument, `$response`, which is a `Lightning\Response` object (see below for more details on interacting with responses)

##### Implementation
To subscribe to a route with Lightning, you need to call the `subscribe()` function with the following parameters:

|Parameter   |Type    |Default |Description                                |
|:----------:|:------:|:------:|-------------------------------------------|
|**topic**   |string  |(none)  |The topic you want to subscribe to with any inline variables or wildcards you want to use.     |
|**qos**     |integer |0       |The quality of service level you want for the published message. (0=at most once, 1=at least once, 2=exactly once). [Learn more](http://www.hivemq.com/blog/mqtt-essentials-part-6-mqtt-quality-of-service-levels)|
|**callback**|string  |(none)  |The callback you want to be invoked upon recieving a message.|

Example:
```php
<?php

require 'vendor/autoload.php';

function exampleCallback(\Lightning\Response $response) {
    $message = $response->getMessage();
}

$host = 'mqtt.example.com';
$port = 1883;
$clientID = md5(uniqid());

$mqtt = new \Lightning\App($host, $port, $clientID);

if (!$mqtt->connect()) {
    exit(1);
}

// A callback defined inline at the time of subscribing
$mqtt->subscribe('/users/+id/status', 0, function (\Lightning\Response $response) {
    $message = $response->getMessage();
});

// A callback referencing another previously defined function
$mqtt->subscribe('/users/+id/status', 0, 'exampleCallback');

// Listen will poll for new messages sent to this client
$mqtt->listen();
```


#### Working with Responses
Lightning responses are modeled closely after PSR HTTP responses, including a number of useful methods for retrieving information about the message you receive.

##### Methods
|Method                  |Parameters |Returns        |Description                                |
|------------------------|:---------:|:-------------:|-------------------------------------------|
|**getMessage()**        |(none)     |string         |Returns the message recieved.              |
|**getRoute()**          |(none)     |string         |Returns the full topic that we received a message on|
|**getReceived()**       |(none)     |integer        |Returns the UNIX timestamp that the message was received|
|**attr()**              |$key       |string/null    |Fetches the value of that attribute from the topic (used for getting inline variable values)|
|**getAttributes()**     |(none)     |array          |Gets all the attributes parsed out of the response topic|
|**hasAttr()**           |$key       |boolean        |Returns whether the response has a value for a specific key|
|**getWildcard()**       |(none)     |string/null    |Returns the wildcard portion of the topic if one is defined|
|**hasWildcard()**       |(none)     |boolean        |Returns whether the response has a wildcard defined|
|**getSubscribedTopic()**|(none)     |Lightning\Topic|Returns the original topic object that we constructed for the subscription|

##### Example
```
<?php
require 'vendor/autoload.php';

$host = 'mqtt.example.com';
$port = 1883;
$clientID = md5(uniqid());

$mqtt = new \Lightning\App($host, $port, $clientID);

if (!$mqtt->connect()) {
    exit(1);
}

// Example with inline variable
$mqtt->subscribe('/users/+id/status', 0, function (\Lightning\Response $response) {
    $message = $response->getMessage();
    $topic = $response->getRoute();
    $id = $response->attr('id'); // fetches the id attribute from the topic
});

// Example with wildcard
$mqtt->subscribe('/users/#', 0, function (\Lightning\Response $response) {
    $message = $response->getMessage();
    $topic = $response->getRoute();
    $wildcard = $response->getWildcard(); // fetches the id attribute from the topic
});

$mqtt->listen();
```

### Contributing
If you find a problem with the Lightning implementation, please open an issue. Improvements and suggestions are welcome - feel free to open a pull request!

### License
**MIT License**

Copyright (c) 2017 Brandon Hudson

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

Based on the work of Andrew Milsted and the [phpMQTT](http://github.com/bluerhinos/phpMQTT) project
