<?php
/**
 * Created by PhpStorm.
 * User: tikagnus
 * Date: 14/11/2017
 * Time: 22:14
 */

namespace MqqtPhp\Mqtt\Socket;


/**
 * Class Socket
 * @package MqqtPhp\Mqtt\Socket
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
    protected $socketTimeout = 60;

    /**
     * @var string
     */
    protected $caFile;

    /**
     * Socket constructor.
     * @param string $address
     * @param int $port
     * @param int $timeout
     * @param string $protocol
     * @param int $socketTimeout
     * @param string|null $caFile
     */
    public function __construct(string $address = '127.0.0.1', int $port = 1883, int $timeout = 60, string $protocol = 'tcp', int $socketTimeout = 60, string $caFile = null)
    {
        $this->address = $address;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->protocol = $protocol;
        $this->socketTimeout = $socketTimeout;
        $this->caFile = $caFile;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function initSocket()
    {
        if (!is_null($this->socket) && strtolower(get_resource_type($this->socket)) != 'unknown') {
            return $this;
        }

        $errorNumber = null;
        $errorMessage = null;

        if ($this->caFile) {
            $this->socket = stream_socket_client($this->protocol . '://' . $this->address . ':' . $this->port,
                $errorNumber,
                $errorMessage,
                $this->timeout,
                STREAM_CLIENT_CONNECT,
                stream_context_create([
                    'ssl' => [
                        'verify_peer_name' => true,
                        'cafile' => $this->caFile
                    ]
                ])
            );
        } else {
            $this->socket = stream_socket_client($this->protocol . '://' . $this->address . ':' . $this->port,
                $errorNumber,
                $errorMessage,
                $this->timeout
            );
        }

        if (!$this->socket) {
            throw new \Exception($errorMessage, $errorNumber);
        }

        stream_set_timeout($this->socket, $this->socketTimeout);
        stream_set_blocking($this->socket, 0);

        return $this;
    }

    /**
     * @param string $string
     * @param null $length
     * @return $this
     */
    public function write(string $string, $length = null)
    {
        if (!is_null($length)) {
            fwrite($this->socket, $string, $length);
        } else {
            fwrite($this->socket, $string);
        }

        return $this;
    }

    /**
     * @param int $size
     * @param bool $binary
     * @return int string
     */
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
            $remaining = $size - strlen($buff);
        }

        return $buff;
    }

    /**
     * @return int
     */
    public function getBufferLength()
    {
        $multiplier = 1;
        $length = 0;
        do {
            $digit = ord($this->read(1));
            $length += ($digit & 127) * $multiplier;
            $multiplier *= 128;
        } while (($digit & 128) != 0);

        return $length;
    }

    /**
     * @return bool
     */
    public function end()
    {
        return feof($this->socket);
    }

    /**
     * @return $this
     */
    public function close()
    {
        fclose($this->socket);
        return $this;
    }

    /**
     * @return $this
     */
    public function shutdown()
    {
        stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
        return $this;
    }

}