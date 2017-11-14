<?php


namespace PhpMqqt;

use PhpMqqt\Content\Payload;
use PhpMqqt\Content\VariableHeader;

class PhpMqqt
{
    protected $socket;
    protected $messageId = 1;
    protected $keepAlive = 60;
    protected $lastTime;
    protected $topics = [];
    protected $debug = true;
    protected $address;
    protected $port;
    protected $clientId;
    protected $will;
    protected $clean;
    protected $username;
    protected $password;

    protected $caFile;

    protected $buff;

    function __construct(string $address = '127.0.0.1', int $port = 1883, string $clientId = null, $caFile = NULL)
    {
        $this->address = $address;
        $this->port = $port;
        $this->clientId = $clientId ? $clientId : '12345';
        $this->caFile = $caFile;

//				$this->broker($address, $port, $clientid, $cafile);
    }


    function connectAuto($clean = true, $will = NULL, $username = NULL, $password = NULL)
    {
        while ($this->connect($clean, $will, $username, $password) == false) {
            sleep(10);
        }
        return true;
    }

    function connect($clean = true, $will = NULL, $username = NULL, $password = NULL)
    {
        $this->clean = $clean;
        $this->will = $will;
        $this->username = $username;
        $this->password = $password;

        $this->openSocket();

        return $this->connectToBroker();
    }

    protected function openSocket()
    {
        $protocol = 'tcp';
        $timeout = 60;
        $socketContext = null;
        $flags = STREAM_CLIENT_CONNECT;
        $errorNumber = null;
        $errorMessage = null;

//        if ($this->caFile) {
//            $socketContext = stream_context_create([
//                'ssl' => [
//                    'verify_peer_name' => true,
//                    'cafile' => $this->caFile
//                ]]);
//            $protocol = 'tcp';
//        }
        //because of stream_socket_client() last parameter not accept any value;
//        if ($this->caFile) {
//            $this->socket = stream_socket_client($protocol . '://' . $this->address . ':' . $this->port,
//                $errorNumber,
//                $errorMessage,
//                $timeout,
//                $flags,
//                $socketContext
//            );

//        } else {
//        dd($protocol . '://' . $this->address . ':' . $this->port,
//            $errorNumber,
//            $errorMessage,
//            $timeout,
//            $flags);

        $this->socket = stream_socket_client($protocol . '://' . $this->address . ':' . $this->port,
            $errorNumber,
            $errorMessage,
            $timeout,
            $flags
        );

//        }

        if (!$this->socket) {
            if ($this->debug) {
                error_log("stream_socket_create() $errorNumber, $errorMessage \n");
            }
            return false;
        }

        stream_set_timeout($this->socket, 5);
        stream_set_blocking($this->socket, 0);

        return $this;
    }


    protected function connectToBroker()
    {
        $varHead = new VariableHeader();
        $varHead->push($this->clean ? 2 : 0);
        if (!is_null($this->will)) {

            $varHead->push(4);
            $varHead->push($this->will['qos'] << 3);
            if ($this->will['retain']) {
                $varHead->push(32);
            }
        }

        $varHead->push($this->username ? 128 : 0);
        $varHead->push($this->password ? 64 : 0);

        $payload = new Payload();

        $payload->push([
            chr(0x00),
            chr(0x06),
            chr(0x4d),
            chr(0x51),
            chr(0x49),
            chr(0x73),
            chr(0x64),
            chr(0x70),
            chr(0x03)
        ]);


        $payload->push(chr($varHead->getContent()));

        $payload->push([
            chr($this->keepAlive >> 8),
            chr($this->keepAlive & 0xff),
        ])->convertPush($this->clientId);


        if ($this->will != NULL) {
            $payload->convertPush([
                $this->will['topic'],
                $this->will['content']
            ]);

        }

        if ($this->username || $this->password) {
            $payload->convertPush([
                $this->username,
                $this->password
            ]);

        }

        $payloadHeader = chr(0x10) . chr($payload->getLength());
//        dd($payloadHeader);
        fwrite($this->socket, $payloadHeader, 2);

        fwrite($this->socket, $payload->getContent());

        dd($payloadHeader, $payload->getContent());


        $string = $this->readSocket(4);

//        dd($this);

        if (ord($string{0}) >> 4 == 2 && $string{3} == chr(0)) {
            if ($this->debug) {
                echo "Connected to Broker\n";
            }
        } else {
            error_log(sprintf("Connection failed! (Error: 0x%02x 0x%02x)\n",
                ord($string{0}), ord($string{3})));
            return false;
        }

        $this->lastTime = time();

        return true;
    }

    /* read: reads in so many bytes */
    function readSocket($int = 8192, $nb = false)
    {
        $string = "";
        $togo = $int;

        if ($nb) {
            return fread($this->socket, $togo);
        }

        while (!feof($this->socket) && $togo > 0) {
            $fread = fread($this->socket, $togo);
            $string .= $fread;
            $togo = $int - strlen($string);
        }


        return $string;
    }

    /* subscribe: subscribes to topics */
    function subscribe($topics, $qos = 0)
    {
        $payload = new Payload();

        $payload->push([
            chr($this->messageId >> 8),
            chr($this->messageId % 256)
        ]);

        foreach ($topics as $topicName => $topic) {
            $payload->convertPush($topicName)
                ->push(chr($topic["qos"]));
            $this->topics[$topicName] = $topic;
        }

        $cmd = 0x80;
        $cmd += ($qos << 1);

        $head = chr($cmd);
        $head .= chr($payload->getLength());

        fwrite($this->socket, $head, 2);
        fwrite($this->socket, $payload->getContent(), $payload->getLength());

        $string = $this->readSocket(2);
        $bytes = ord(substr($string, 1, 1));
        $string = $this->readSocket($bytes);
    }

    /* ping: sends a keep alive ping */
    function ping()
    {
        $head = chr(0xc0) . chr(0x00);
        fwrite($this->socket, $head, 2);
        if ($this->debug) {
            echo "ping sent\n";
        }
    }

    /* disconnect: sends a proper disconect cmd */
    protected function disconnect()
    {
        $head = chr(0xe0) . chr(0x00);
        fwrite($this->socket, $head, 2);
    }

    /* close: sends a proper disconnect, then closes the socket */
    function close()
    {
        $this->disconnect();
        stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
    }

    /* publish: publishes $content on a $topic */
    function publish($topic, $content, $qos = 0, $retain = 0)
    {
        $payload = new Payload();

        $payload->convertPush($topic);

        if ($qos) {
            $id = $this->messageId++;
            $payload->push($id >> 8)
                ->push($id % 256);
        }

        $payload->push($content, strlen($content));

        $head = " ";
        $cmd = 0x30;
        if ($qos) $cmd += $qos << 1;
        if ($retain) $cmd += 1;

        $head{0} = chr($cmd);
        $head .= $this->setmsglength($payload->getLength());

        fwrite($this->socket, $head, strlen($head));
        fwrite($this->socket, $payload->getContent(), $payload->getLength());
    }

    /* message: processes a recieved topic */
    function message($msg)
    {

        var_dump('Message Raw:' . $msg);

        $tlen = (ord($msg{0}) << 8) + ord($msg{1});
        $topic = substr($msg, 2, $tlen);
        $msg = substr($msg, ($tlen + 2));
        $found = 0;
        foreach ($this->topics as $topicName => $top) {
            if (preg_match("/^" . str_replace("#", ".*",
                    str_replace("+", "[^\/]*",
                        str_replace("/", "\/",
                            str_replace("$", '\$',
                                $topicName)))) . "$/", $topic)) {
                if (is_callable($top['function'])) {
                    call_user_func($top['function'], $topic, $msg);
                    $found = 1;
                }
            }
        }

        if ($this->debug && !$found) echo "msg recieved but no match in subscriptions\n";
    }

    /* proc: the processing loop for an "always on" client
        set true when you are doing other stuff in the loop good for watching something else at the same time */
    function proc($loop = true)
    {

        if (feof($this->socket)) {
            if ($this->debug) {
                echo "eof receive going to reconnect for good measure\n";
            }
            fclose($this->socket);
            $this->connectAuto(false);
            if (count($this->topics)) {
                $this->subscribe($this->topics);
            }
        }

        $byte = $this->readSocket(1, true);

        if (!strlen($byte)) {
            if ($loop) {
                usleep(100000);
            }

        } else {

            $cmd = (int)(ord($byte) / 16);
            if ($this->debug) echo "Recevid: $cmd\n";

            $multiplier = 1;
            $value = 0;
            do {
                $digit = ord($this->readSocket(1));
                $value += ($digit & 127) * $multiplier;
                $multiplier *= 128;
            } while (($digit & 128) != 0);

            if ($this->debug) echo "Fetching: $value\n";

            if ($value)
                $string = $this->readSocket($value);

            if ($cmd) {
                switch ($cmd) {
                    case 3:
                        $this->message($string);
                        break;
                }

                $this->lastTime = time();
            }
        }

        if ($this->lastTime < (time() - $this->keepAlive)) {
            if ($this->debug) {
                echo "not found something so ping\n";
            }
            $this->ping();
        }


        if ($this->lastTime < (time() - ($this->keepAlive * 2))) {
            if ($this->debug) {
                echo "not seen a package in a while, disconnecting\n";
            }
            fclose($this->socket);
            $this->connectAuto(false);
            if (count($this->topics))
                $this->subscribe($this->topics);
        }

        return 1;
    }

    /* getmsglength: */
    function getmsglength(&$msg, &$i)
    {

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
    function setmsglength($len)
    {
        $string = "";
        do {
            $digit = $len % 128;
            $len = $len >> 7;
            // if there are more digits to encode, set the top bit of this digit
            if ($len > 0)
                $digit = ($digit | 0x80);
            $string .= chr($digit);
        } while ($len > 0);
        return $string;
    }

}
