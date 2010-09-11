<?php

/*
 	phpMQTT
	A simple php class to connect/publish/subscribe to an MQTT broker
 
*/

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

/* phpMQTT */
class phpMQTT {

	private $socket; 			/* holds the socket	*/
	private $msgid = 1;			/* counter for message id */
	public $keepalive = 10;		/* default keepalive timmer */
	public $timesinceping;		/* host unix time, used to detect disconects */
	public $topics = array(); 	/* used to store currently subscribed topics */
	public $debug = false;		/* should output debug messages */
	public $address;			/* broker address */
	public $port;				/* broker port */
	public $clientid;			/* client id sent to brocker */
	
	function __construct($address, $port, $clientid){
		$this->broker($address, $port, $clientid);
	}
	
	/* sets the broker details */
	function broker($address, $port, $clientid){
		$this->address = $address;
		$this->port = $port;
		$this->clientid = $clientid;		
	}
	
	/* connects to the broker 
		inputs: $clean: should the client send a clean session flag */
	function connect($clean = true){
		
		$address = gethostbyname($this->address);	
		$this->socket = fsockopen($address, $this->port, $errno, $errstr, 60);
		
		stream_set_timeout($this->socket, 5);
		stream_set_blocking($this->socket,true);
		
		if (!$this->socket ) {
		    error_log("fsockopen() $errno, $errstr \n");
			exit();
		}
			
		$i = 0;
		$buffer = " ";

		$buffer{$i++} = chr(0x00);
		$buffer{$i++} = chr(0x06);
		$buffer{$i++} = chr(0x4d);
		$buffer{$i++} = chr(0x51);
		$buffer{$i++} = chr(0x49);
		$buffer{$i++} = chr(0x73);
		$buffer{$i++} = chr(0x64);
		$buffer{$i++} = chr(0x70);
		$buffer{$i++} = chr(0x03);

		//No Will
		$var = 0;
		if($clean) $var+=2;
		$buffer{$i++} = chr($var);
		//Keep alive
		$buffer{$i++} = chr(0x00);
		$buffer{$i++} = chr($this->keepalive);

		$buffer .= $this->strwritestring($this->clientid,$i);

		$head = " ";
		$head{0} = chr(0x10);
		$head{1} = chr($i);
	
		fwrite($this->socket, $head, 2);
		fwrite($this->socket,  $buffer);
		              
	 	$string = $this->read(4);
	
		if($string{3} == chr(0)){
			if($this->debug) echo "Connected to Broker\n"; 
		}else{
			error_log(sprintf("Connection failed! (Error: 0x%02x)\n", ord($string{3})));
			exit(1);
		}
		
		$this->timesinceping = time();
		
	}
	
	/* read: reads in so many bytes */
	function read($int = 8192 ){
		$string="";
		$togo = $int;
		while (!feof($this->socket) && $togo>0) {
			$togo = $int - strlen($string);
			if($togo) $string .= fread($this->socket, $togo);
		}
		
		return $string;
	}
	
	/* subscribe: subscribes to topics */
	function subscribe($topics, $qos = 0){
		$i = 0;
		$buffer = " ";
		$id = $this->msgid;
		$buffer{$i++} = chr($id >> 8);
		$buffer{$i++} = chr($id % 256);
	
		foreach($topics as $key => $topic){
			$buffer .= $this->strwritestring($key,$i);
			$buffer{$i++} = chr($topic["qos"]);
			$this->topics[$key] = $topic; 
		}
		
		$cmd = 0x80;
		//$qos
		$cmd +=	($qos << 1);

		$head = " ";
		$head{0} = chr($cmd);
		$head{1} = chr($i);

		fwrite($this->socket, $head, 2);
		fwrite($this->socket, $buffer, $i);
		$string = $this->read(2);
		$bytes = ord($string{1});
		$string = $this->read($bytes);
	}

	/* ping: sends a keep alive ping */
	function ping(){
			$head = " ";
			$head{0} = chr(0xc0);		
			$head{1} = chr(0x00);
			fwrite($this->socket, $head, 2);
			if($this->debug) echo "ping sent\n";
	}
	
	/* disconnect: sends a proper disconect cmd */
	function disconnect(){
			$head = " ";
			$head{0} = chr(0xe0);		
			$head{1} = chr(0x00);
			fwrite($this->socket, $head, 2);
	}
	
	/* close: sends a proper disconect, then closes the socket */
	function close(){
	 	$this->disconnect();
		fclose($this->socket);	
		exit(0);
	}

	/* publish: publishes $content on a $topic */
	function publish($topic, $content, $qos = 0, $retain = 0){
	
		$i = 0;
		$buffer = "";
		
		$buffer .= $this->strwritestring($topic,$i);

		$buffer .= $this->strwritestring($content,$i);

		if($qos){
			$id = $this->msgid++;
			$buffer{$i++} = chr($id >> 8);
		 	$buffer{$i++} = chr($id % 256);
		}
		
		$head = " ";
		$cmd = 0x30;
		if($qos) $cmd += $qos << 1;

		$head{0} = chr($cmd);		
		$head{1} = chr($i);
		
		fwrite($this->socket, $head, 2);
		fwrite($this->socket, $buffer, $i);

	}
	
	/* message: processes a recieved topic */
	function message($msg){
		 	$tlen = (ord($msg{0})<<8) + ord($msg{1});
			$topic = substr($msg,2,$tlen);
			$msg = substr($msg,($tlen+2));
			$found = 0;
			foreach($this->topics as $key=>$top){
				if(preg_match("/^".str_replace("#",".*",str_replace("+","[^\/]*",str_replace("/","\/",$key)))."$/",$topic) ){
					if(function_exists($top['function'])){
						call_user_func($top['function'],$topic,$msg);
						$found = 1;
					}
				}
			}
			
			if($this->debug && !$found) echo "msg recieved but no match in subscriptions\n";
	}

	/* proc: the processing loop for an "allways on" client */	
	function proc(){
	
		while(1){
			$sockets = array($this->socket);
			$w = $e = NULL;

			if($this->debug) echo "start wait\n";

			if(stream_select($sockets, $w, $e, ($this->keepalive-1))){
				if($this->debug) echo "found something\n";
				
				$byte = fgetc($this->socket);
				$cmd = (int)(ord($byte)/16);
				if($this->debug) echo "Recevid: $cmd\n";
				
				$multiplier = 1; 
				$value = 0;
				$i=0;
				do{
				  $digit = ord(fgetc($this->socket));
				  $value += ($digit & 127) * $multiplier; 
				  $multiplier *= 128;
				  $i++;
				}while (($digit & 128) != 0);

				$string = $this->read($value,"fetch");
				if($cmd){
					switch($cmd){
						case 3:
							$this->message($string);
						break;
					}
				
					$this->timesinceping = time();
				}
				
			}else{
				
				
				if($this->debug) echo "not found something\n";
				$this->ping();	
			}
			
			if($this->timesinceping<(time()-($this->keepalive*2))){
					if($this->debug) echo "not seen a package in a while, disconnecting\n";
					fclose($this->socket);
					$this->connect(false);
			}

		}
	}
	
	/* getmsglength: */
	function getmsglength(&$msg, &$i){
		
		$multiplier = 1; 
		$value = 0 ;
		do{
		  $digit = ord($msg{$i});
		  $value += ($digit & 127) * $multiplier; 
		  $multiplier *= 128;
		  $i++;
		}while (($digit & 128) != 0);
		
		return $value;
	}
	
	/* strwritestring: writes a string to a buffer */
	function strwritestring($str, &$i){
		$ret = " ";
		$len = strlen($str);
		$msb = $len >> 8;
		$lsb = $len % 256;
		$ret{0} = chr($msb);
		$ret{1} = chr($lsb);
		$ret .= $str;
		$i += ($len+2);
		return $ret;
	}
	
	function printstr($string){
		$strlen = strlen($string);
			for($j=0;$j<$strlen;$j++){
				$num = ord($string{$j});
				if($num > 31) 
					$chr = $string{$j}; else $chr = " ";
				printf("%4d: %08b : 0x%02x : %s \n",$j,$num,$num,$chr);
			}
	}
}

?>
