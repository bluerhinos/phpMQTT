<?php

namespace Bluerhinos;

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
    public $will;				/* stores the will of the client */
    private $username;			/* stores username */
    private $password;			/* stores password */
    public $cafile;

    public function __construct($address, $port, $clientid, $cafile = NULL){
        $this->broker($address, $port, $clientid, $cafile);
    }

    /* sets the broker details */
    public function broker($address, $port, $clientid, $cafile = NULL){
        $this->address = $address;
        $this->port = $port;
        $this->clientid = $clientid;
        $this->cafile = $cafile;
    }

    public function connect_auto($clean = true, $will = NULL, $username = NULL, $password = NULL){
        while($this->connect($clean, $will, $username, $password)==false){
            sleep(10);
        }
        return true;
    }

    /* connects to the broker
        inputs: $clean: should the client send a clean session flag */
    public function connect($clean = true, $will = NULL, $username = NULL, $password = NULL){

        if($will) $this->will = $will;
        if($username) $this->username = $username;
        if($password) $this->password = $password;


        if ($this->cafile) {
            $socketContext = stream_context_create(["ssl" => [
                "verify_peer_name" => true,
                "cafile" => $this->cafile
            ]]);
            $this->socket = stream_socket_client("tls://" . $this->address . ":" . $this->port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $socketContext);
        } else {
            $this->socket = stream_socket_client("tcp://" . $this->address . ":" . $this->port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT);
        }

        if (!$this->socket ) {
            if($this->debug) error_log("stream_socket_create() $errno, $errstr \n");
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
        if($this->will != NULL){
            $var += 4; // Set will flag
            $var += ($this->will['qos'] << 3); //Set will qos
            if($this->will['retain'])	$var += 32; //Set will retain
        }

        if($this->username != NULL) $var += 128;	//Add username to header
        if($this->password != NULL) $var += 64;	//Add password to header

        $buffer .= chr($var); $i++;

        //Keep alive
        $buffer .= chr($this->keepalive >> 8); $i++;
        $buffer .= chr($this->keepalive & 0xff); $i++;

        $buffer .= $this->strwritestring($this->clientid,$i);

        //Adding will to payload
        if($this->will != NULL){
            $buffer .= $this->strwritestring($this->will['topic'],$i);
            $buffer .= $this->strwritestring($this->will['content'],$i);
        }

        if($this->username) $buffer .= $this->strwritestring($this->username,$i);
        if($this->password) $buffer .= $this->strwritestring($this->password,$i);

        $head = "  ";
        $head{0} = chr(0x10);
        $head{1} = chr($i);

        fwrite($this->socket, $head, 2);
        fwrite($this->socket,  $buffer);

        $string = $this->read(4);

        if(ord($string{0})>>4 == 2 && $string{3} == chr(0)){
            if($this->debug) echo "Connected to Broker\n";
        }else{
            error_log(sprintf("Connection failed! (Error: 0x%02x 0x%02x)\n",
                ord($string{0}),ord($string{3})));
            return false;
        }

        $this->timesinceping = time();

        return true;
    }

    /* read: reads in so many bytes */
    public function read($int = 8192, $nb = false){

        //	print_r(socket_get_status($this->socket));

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

    /* subscribe: subscribes to topics */
    public function subscribe($topics, $qos = 0)
    {
        $i = 0;
        $buffer = "";
        $id = $this->msgid;
        $buffer .= chr($id >> 8);
        $i++;
        $buffer .= chr($id % 256);
        $i++;


        $buffer .= $this->strwritestring($topics, $i);
        $buffer .= chr(0);
        $i++;
        $this->topics[$topics] = $topics;


        $cmd = 0x80;
        //$qos
        $cmd += ($qos << 1);


        $head = chr($cmd);
        $head .= chr($i);

        fwrite($this->socket, $head, 2);
        fwrite($this->socket, $buffer, $i);
        $string = $this->read(2);

        $bytes = ord(substr($string, 1, 1));
        $string = $this->read($bytes);
        //teste
        if(1){
            $sockets = array($this->socket);
            $w = $e = NULL;
            $cmd = 0;

            //$byte = fgetc($this->socket);
            if(feof($this->socket)){
                if($this->debug) echo "eof receive going to reconnect for good measure\n";
                fclose($this->socket);
                $this->connect_auto(false);
                if(count($this->topics))
                    $this->subscribe($this->topics);
            }

            $byte = $this->read(1, true);

            $cmd = (int)(ord($byte)/16);
            if($this->debug) echo "Recevid: $cmd\n";
            $multiplier = 1;
            $value = 0;
            do{
                $digit = ord($this->read(1));
                $value += ($digit & 127) * $multiplier;
                $multiplier *= 128;
            }while (($digit & 128) != 0);
            $string = $this->read($value);

            return $string;
        }
    }
    /* ping: sends a keep alive ping */
    public function ping(){
        $head = " ";
        $head = chr(0xc0);
        $head .= chr(0x00);
        fwrite($this->socket, $head, 2);
        if($this->debug) echo "ping sent\n";
    }

    /* disconnect: sends a proper disconect cmd */
    public function disconnect(){
        $head = " ";
        $head{0} = chr(0xe0);
        $head{1} = chr(0x00);
        fwrite($this->socket, $head, 2);
    }

    /* close: sends a proper disconect, then closes the socket */
    function close(){
        $this->disconnect();
        stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
    }

    /* publish: publishes $content on a $topic */
    public function publish($topic, $content,  $retain = 0){
        $qos = 0;
        $i = 0;
        $buffer = "";

        $buffer .= $this->strwritestring($topic,$i);

        //$buffer .= $this->strwritestring($content,$i);

        $buffer .= $content;
        $i+=strlen($content);


        $head = " ";
        $cmd = 0x30;
        if($qos) $cmd += $qos << 1;
        if($retain) $cmd += 1;

        $head{0} = chr($cmd);
        $head .= $this->setmsglength($i);

        fwrite($this->socket, $head, strlen($head));
        fwrite($this->socket, $buffer, $i);

    }

    /* proc: the processing loop for an "allways on" client
        set true when you are doing other stuff in the loop good for watching something else at the same time */

    /* setmsglength: */
    public function setmsglength($len){
        $string = "";
        do{
            $digit = $len % 128;
            $len = $len >> 7;
            // if there are more digits to encode, set the top bit of this digit
            if ( $len > 0 )
                $digit = ($digit | 0x80);
            $string .= chr($digit);
        }while ( $len > 0 );
        return $string;
    }

    /* strwritestring: writes a string to a buffer */
    public function strwritestring($str, &$i){
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
}
