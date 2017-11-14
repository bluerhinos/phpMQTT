<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 14/11/2017
 * Time: 22:14
 */

namespace PhpMqqt\Mqtt\Socket;


/**
 * Class Socket
 * @package PhpMqqt\Mqtt
 */
class Socket
{
    /**
     * @var resource
     */
    public $socket;
    /**
     * @var string
     */
    protected $address;
    /**
     * @var int
     */
    protected $port;
    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $protocol = 'tcp';
    /**
     * @var int
     */
    protected $timeout = 60;
    /**
     * @var int
     */
    protected $socketTimeout = 5;

    /**
     * @var int
     */
    protected $flags = STREAM_CLIENT_CONNECT;

    /**
     * Socket constructor.
     * @param string $address
     * @param int $port
     */
    public function __construct(string $address = '127.0.0.1', int $port = 1883)
    {
        $this->address = $address;
        $this->port = $port;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function initSocket()
    {
        $errorNumber = null;
        $errorMessage = null;

//        dd($this->protocol . '://' . $this->address . ':' . $this->port,
//            $errorNumber,
//            $errorMessage,
//            $this->timeout,
//            $this->flags
//        );

        $this->socket = stream_socket_client($this->protocol . '://' . $this->address . ':' . $this->port,
            $errorNumber,
            $errorMessage,
            $this->timeout,
            $this->flags
        );

        if (!$this->socket) {
            throw new \Exception($errorMessage, $errorNumber);
        }

        stream_set_timeout($this->socket, $this->socketTimeout);
        stream_set_blocking($this->socket, 0);

        return $this;
    }

    public function write(string $string, $length = null)
    {
        fwrite($this->socket, $string, $length);
        return $this;
    }

    public function read(int $size = 8192, bool $binary = false)
    {
        if ($binary) {
            return fread($this->socket, $size);
        }

        $buff = "";
        $remaining = $size;
        while (!feof($this->socket) && $remaining > 0) {
            $tmp = fread($this->socket, $remaining);
            $buff .= $tmp;
            dd($buff);
            $remaining = $size - strlen($buff);
        }

        return $buff;
    }


}