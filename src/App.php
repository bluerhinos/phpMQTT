<?php
/*
	Licence

	Copyright (c) 2010 Blue Rhinos Consulting | Andrew Milsted
	andrew@bluerhinos.co.uk | http://www.bluerhinos.co.uk

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.
	
*/

namespace phpMQTT;

use phpMQTT\Topic;
use phpMQTT\Response;

class App {

	private $socket; 			/* holds the socket	*/
	private $msgid = 1;			/* counter for message id */
	public $keepalive = 10;		/* default keepalive timmer */
	public $timeSincePing;		/* host unix time, used to detect disconects */
	public $topics = array(); 	/* used to store currently subscribed topics */
	public $debug = false;		/* should output debug messages */
	public $address;			/* broker address */
	public $port;				/* broker port */
	public $clientID;			/* client id sent to brocker */
	public $will;				/* stores the will of the client */
	private $username;			/* stores username */
	private $password;			/* stores password */
	private $subscribingTopics = array();

	/**
	 * Constructor.
	 * @param string $address 
	 * @param string $port
	 * @param string $clientID
	 * @param string $username
	 * @param string $password
	 */
	function __construct($address, $port, $clientID, $username = '', $password = '') {
		$this->address = $address;
		$this->port = $port;
		$this->clientID = $clientID;

		if($username) {
			$this->username = $username;
		}

		if($password) {
			$this->password = $password;
		}
	}

	/**
	 * Toggles debugging.
	 * @param  boolean $enable
	 */
	public function debug($enable = true) {
		$this->debug = $enable;
	}

	/**
	 * Automatically attempts to connect until a connection is established.
	 * @param  boolean $clean should the client send a clean session flag.
	 * @param  array   $will  the will of the client
	 * @return boolean
	 */
	public function connect_auto($clean = true, $will = NULL) {
		while( $this->connect($clean, $will) == false ) {
			sleep(10);
		}

		return true;
	}

	/**
	 * Connects to the MQTT broker.
	 * @param  boolean $clean should the client send a clean session flag.
	 * @param  string  $will  the will of the client
	 * @return boolean
	 */
	public function connect($clean = true, $will = NULL) {
		if ($will) {
			$this->will = $will;
		}

		$address = gethostbyname($this->address);
		$this->socket = fsockopen($address, $this->port, $errno, $errstr, 60);

		if (!$this->socket ) {
		    if($this->debug) {
		    	error_log("fsockopen() $errno, $errstr \n");
		    }

			return false;
		}

		stream_set_timeout($this->socket, 5);
		stream_set_blocking($this->socket, 0);

		$i = 0;
		$buffer = "";

		$buffer .= chr(0x00); $i++;
		$buffer .= chr(0x06); $i++;
		$buffer .= chr(0x4d); $i++;
		$buffer .= chr(0x51); $i++;
		$buffer .= chr(0x49); $i++;
		$buffer .= chr(0x73); $i++;
		$buffer .= chr(0x64); $i++;
		$buffer .= chr(0x70); $i++;
		$buffer .= chr(0x03); $i++;

		//No Will
		$var = 0;
		if($clean) $var+=2;

		//Add will info to header
		if ($this->will != NULL) {
			$var += 4; // Set will flag
			$var += ($this->will['qos'] << 3); //Set will qos

			if ($this->will['retain']) {
				$var += 32; //Set will retain
			}
		}

		if ($this->username != NULL) {
			$var += 128;	//Add username to header
		}

		if($this->password != NULL) {
			$var += 64;	//Add password to header
		}

		$buffer .= chr($var); $i++;

		//Keep alive
		$buffer .= chr($this->keepalive >> 8); $i++;
		$buffer .= chr($this->keepalive & 0xff); $i++;

		$buffer .= $this->strwritestring($this->clientID, $i);

		//Adding will to payload
		if ($this->will != NULL) {
			$buffer .= $this->strwritestring($this->will['topic'], $i);  
			$buffer .= $this->strwritestring($this->will['content'], $i);
		}

		if ($this->username) {
			$buffer .= $this->strwritestring($this->username, $i);
		}

		if ($this->password) {
			$buffer .= $this->strwritestring($this->password, $i);
		}

		$head = "  ";
		$head{0} = chr(0x10);
		$head{1} = chr($i);

		fwrite($this->socket, $head, 2);
		fwrite($this->socket,  $buffer);

	 	$string = $this->read(4);

		if (ord($string{0})>>4 == 2 && $string{3} == chr(0)) {
			if($this->debug) {
				echo "Connected to Broker\n";
			}
		} else {
			error_log(sprintf("Connection failed! (Error: 0x%02x 0x%02x)\n", 
			                        ord($string{0}),ord($string{3})));
			return false;
		}

		$this->timeSincePing = time();

		return true;
	}

	/**
	 * Reads in a specific number of bytes.
	 * @param  integer $int
	 * @param  boolean $nb
	 * @return string
	 */
	private function read($int = 8192, $nb = false) {		
		$string="";
		$togo = $int;

		if($nb){
			return fread($this->socket, $togo);
		}
	
		while (!feof($this->socket) && $togo>0) {
			$fread = fread($this->socket, $togo);
			$string .= $fread;
			$togo = $int - strlen($string);
		}
	
		return $string;
	}

	/**
	 * Creates a new topic to listen for.
	 * @param  string   $route    the route to listen on
	 * @param  int      $qos      the quality of service for the topic
	 * @param  callable $callable a method to call on response
	 */
	public function subscribe($route, $qos, $callable) {
		$topic = new Topic($route, $qos, $callable);
		$route = $topic->getRoute();
		$this->topics[$route->getMQTT()] = $topic;
		$this->initSubscriptions();
	}

	private function initSubscriptions($qos = 0) {
		$i = 0;
		$buffer = "";
		$id = $this->msgid;
		$buffer .= chr($id >> 8);  $i++;
		$buffer .= chr($id % 256);  $i++;

		foreach ($this->topics as $key => $topic) {
			$buffer .= $this->strwritestring($key, $i);
			$buffer .= chr($topic->getQOS());
			$i++;
		}

		$cmd = 0x80;
		//$qos
		$cmd +=	($qos << 1);
		$head = chr($cmd);
		$head .= chr($i);
		
		fwrite($this->socket, $head, 2);
		fwrite($this->socket, $buffer, $i);
		$string = $this->read(2);
		
		$bytes = ord(substr($string,1,1));
		$string = $this->read($bytes);
	}

	/**
	 * Sends a keepalive notice to the MQTT broker.
	 */
	public function ping() {
			$head = " ";
			$head = chr(0xc0);		
			$head .= chr(0x00);
			fwrite($this->socket, $head, 2);
			if($this->debug) echo "ping sent\n";
	}

	/**
	 * Sends a proper disconnect to the MQTT broker.
	 */
	private function disconnect() {
			$head = " ";
			$head{0} = chr(0xe0);		
			$head{1} = chr(0x00);
			fwrite($this->socket, $head, 2);
	}

	/**
	 * Sends a proper disconnect to the MQTT broker and then closes the socket.
	 */
	public function close() {
	 	$this->disconnect();
		fclose($this->socket);	
	}

	/* publish: publishes $content on a $topic */
	/**
	 * Publishes a message to a specified topic.
	 * @param  string  $topic
	 * @param  mixed   $content
	 * @param  integer $qos     quality of service to use for the published content
	 * @param  integer $retain  whether or not the message should be retained
	 */
	public function publish($topic, $content, $qos = 0, $retain = 0) {
		$i = 0;
		$buffer = "";

		$buffer .= $this->strwritestring($topic, $i);

		//$buffer .= $this->strwritestring($content,$i);

		if ($qos) {
			$id = $this->msgid++;
			$buffer .= chr($id >> 8);  $i++;
		 	$buffer .= chr($id % 256);  $i++;
		}

		$buffer .= $content;
		$i+=strlen($content);


		$head = " ";
		$cmd = 0x30;

		if($qos) {
			$cmd += $qos << 1;
		}

		if ($retain) {
			$cmd += 1;
		}

		$head{0} = chr($cmd);		
		$head .= $this->setmsglength($i);

		fwrite($this->socket, $head, strlen($head));
		fwrite($this->socket, $buffer, $i);
	}

	/**
	 * Processes a message.
	 * @param  string $msg
	 */
	private function message($msg) {
	 	$tlen = (ord($msg{0})<<8) + ord($msg{1});
		$topic = substr($msg,2,$tlen);
		$msg = substr($msg,($tlen+2));
		$found = 0;

		foreach ($this->topics as $key => $topObj) {
			if (preg_match("/^".str_replace("#",".*",
					str_replace("+","[^\/]*",
						str_replace("/","\/",
							str_replace("$",'\$',
								$key))))."$/", $topic)) {
				if (is_callable($topObj->getCallable())) {
					$response = new Response($topic, $topObj, $msg);
					call_user_func($topObj->getCallable(), $response);
					$found = 1;
				}
			}
		}

		if($this->debug && !$found) {
			echo "msg recieved but no match in subscriptions\n";
		}
	}

	/* proc: the processing loop for an "allways on" client 
		set true when you are doing other stuff in the loop good for watching something else at the same time */
	/**
	 * The processing loop for an always on client.
	 * Set true when you are doing other things in the the loop - good for watching something else at the same time.
	 * @param  boolean $loop
	 * @return int
	 */
	public function listen($loop = true) {
		while ($loop) {
			$sockets = array($this->socket);
			$w = $e = NULL;
			$cmd = 0;

			if (feof($this->socket)) {
				if($this->debug) {
					echo "eof receive going to reconnect for good measure\n";
				}

				fclose($this->socket);
				$this->connect_auto(false);

				if(count($this->topics)) {
					$this->initSubscriptions();	
				}
			}
			
			$byte = $this->read(1, true);

			if (!strlen($byte)) {
				if ($loop) {
					usleep(100000);
				} 
			} else { 
				$cmd = (int)(ord($byte)/16);

				if ($this->debug) {
					echo "Recevid: $cmd\n";
				}

				$multiplier = 1; 
				$value = 0;

				do{
					$digit = ord($this->read(1));
					$value += ($digit & 127) * $multiplier; 
					$multiplier *= 128;
				} while (($digit & 128) != 0);

				if($this->debug) {
					echo "Fetching: $value\n";
				}
				
				if ($value) {
					$string = $this->read($value,"fetch");
				}

				if ($cmd) {
					switch ($cmd) {
						case 3:
							$this->message($string);
						break;
					}

					$this->timeSincePing = time();
				}
			}

			if ($this->timeSincePing < (time() - $this->keepalive )) {
				if($this->debug) echo "not found something so ping\n";
				$this->ping();	
			}

			if ($this->timeSincePing<(time()-($this->keepalive*2))) {
				if ($this->debug) {
					echo "not seen a package in a while, disconnecting\n";
				}

				fclose($this->socket);
				$this->connect_auto(false);

				if (count($this->topics)) {
					$this->initSubscriptions();
				}
			}
		}

		return 1;
	}

	/* getmsglength: */
	private function getmsglength(&$msg, &$i) {
		$multiplier = 1; 
		$value = 0;

		do {
		  $digit = ord($msg{$i});
		  $value += ($digit & 127) * $multiplier; 
		  $multiplier *= 128;
		  $i++;
		} while (($digit & 128) != 0);

		return $value;
	}


	/* setmsglength: */
	private function setmsglength($len) {
		$string = "";

		do {
		  $digit = $len % 128;
		  $len = $len >> 7;
		  // if there are more digits to encode, set the top bit of this digit
		  if ( $len > 0 )
		    $digit = ($digit | 0x80);
		  $string .= chr($digit);
		} while ( $len > 0 );

		return $string;
	}

	/* strwritestring: writes a string to a buffer */
	private function strwritestring($str, &$i) {
		$ret = " ";
		$len = strlen($str);
		$msb = $len >> 8;
		$lsb = $len % 256;
		$ret = chr($msb);
		$ret .= chr($lsb);
		$ret .= $str;
		$i += ($len+2);

		return $ret;
	}

	private function printstr($string) {
		$strlen = strlen($string);

		for ($j=0; $j<$strlen; $j++) {
			$num = ord($string{$j});

			if ($num > 31) {
				$chr = $string{$j}; 
			} else {
				$chr = " ";
			}

			printf("%4d: %08b : 0x%02x : %s \n",$j,$num,$num,$chr);
		}
	}
}

?>
