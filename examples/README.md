# Output example

Before running each file be sure to review the four connection parameters in the headers.

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

The results shown above corresponds to publisher's two actions:
```console
$ php publish.php
$ php publish.php
```
