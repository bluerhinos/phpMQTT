# Output example

For info on MQTT: https://mntolia.com/fundamentals-mqtt/#4_Advantages_of_MQTT_for_IoT_over_HTTP_UDP

> Before running each file be sure to review the four connection parameters in the headers.

subscribe.php
--
This example is to demonstrate using MQTT for a long-running script that will wait for subscribed topics.
This script is not suitable to be run as web requests, instead should be run on the commandline.

Let `subscribe.php` listening the broker: 
```console
$ php subscribe.php 
Msg Recieved: Fri, 13 Jan 2017 01:58:23 +0000
Topic: bluerhinos/phpMQTT/examples/publishtest

	Hello World! at Fri, 13 Jan 2017 01:58:23 +0000

Msg Recieved: Fri, 13 Jan 2017 01:58:35 +0000
Topic: bluerhinos/phpMQTT/examples/publishtest

	Hello World! at Fri, 13 Jan 2017 01:58:35 +0000

^C
```

publish.php
---
This example will publish a message to a topic.

The results shown above corresponds to publisher's two actions:
```console
$ php publish.php
$ php publish.php
```

When run as a web request you it is ok to just `$mqtt->connect()`, `$mqtt->publish()` and `$mqtt->close()`.
If it is being run as long-running command line script you should run `$mqtt->proc()` regularly in order maintain the connection with the broker.
 
subscribeAndWaitForMessage.php
--
In order to use this library to display messages on a website you can use `$mqtt->subscribeAndWaitForMessage()`, this will subscribe to a topic and then wait for, and return the message.
If you want messages to appear instantly, you should use retained messages (https://mntolia.com/mqtt-retained-messages-explained-example/)  