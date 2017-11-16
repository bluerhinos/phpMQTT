<?php
/**
 * Created by PhpStorm.
 * User: tikagnus
 * Date: 14/11/2017
 * Time: 22:13
 */

namespace MqqtPhp\Mqtt;

use MqqtPhp\Content\Payload;
use MqqtPhp\Content\VariableHeader;
use MqqtPhp\Mqtt\Socket\Socket;
use MqqtPhp\Mqtt\Will\Will;

/**
 * Class AbstractMqtt
 * @package MqqtPhp\Mqtt
 */
abstract class AbstractMqtt
{
    /**
     * @var Socket
     */
    protected $socket;
    /**
     * @var string
     */
    protected $clientId;
    /**
     * @var int
     */
    protected $keepAlive;
    /**
     * @var bool
     */
    protected $clean;
    /**
     * @var Will
     */
    protected $will;
    /**
     * @var string
     */
    protected $user;
    /**
     * @var string
     */
    protected $pass;
    /**
     * @var
     */
    protected $lastActivity;

    /**
     * @var int
     */
    protected $messageId = 0;

    /**
     * AbstractMqtt constructor.
     * @param Socket $socket
     * @param string $clientId
     * @param int $keepAlive
     * @param bool $clean
     * @param Will|null $will
     * @param string|null $user
     * @param string|null $pass
     */
    public function __construct(Socket $socket, string $clientId, int $keepAlive = 60, bool $clean = true, Will $will = null, string $user = null, string $pass = null)
    {
        $this->socket = $socket;
        $this->clientId = $clientId;
        $this->keepAlive = $keepAlive;
        $this->clean = $clean;
        $this->will = $will;
        $this->user = $user;
        $this->pass = $pass;

        $this->connect();
    }

    /**
     * @return $this
     */
    protected function connect()
    {
        $this->socket->initSocket();
        $this->sendConnectPacket();

        return $this;
    }

    /**
     * @return $this
     */
    protected function autoReconnect()
    {
        while (true) {
            try {
                $this->connect();
                return $this;
            } catch (\Exception $e) {

            }
            sleep(10);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function sendConnectPacket()
    {
        $head = new VariableHeader();

        $head->push($this->clean ? 2 : 0);

        if ($this->will) {
            $head->push(4);
            $head->push($this->will->qos() << 3);
            if ($this->will->retain()) {
                $head->push(32);
            }
        }

        $head->push($this->user ? 128 : 0, 0);
        $head->push($this->pass ? 64 : 0, 0);

        $load = new Payload();

        $load->push([
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

        $load->push(chr($head->getContent()));

        $load->push([
            chr($this->keepAlive >> 8),
            chr($this->keepAlive & 0xff)
        ])->convertPush($this->clientId);

        if ($this->will) {
            $load->convertPush([
                $this->will->topic()->name(),
                $this->will->content()
            ]);
        }

        if ($this->user) {
            $load->convertPush($this->user);
        }

        if ($this->pass) {
            $load->convertPush($this->pass);
        }

        $loadHeader = chr(0x10) . chr($load->getLength());

        $this->socket->write($loadHeader, 2)
            ->write($load->getContent());

        $response = $this->socket->read(4);

        if (!(ord($response{0}) >> 4 == 2 && $response{3} == chr(0))) {
            throw new \Exception("Connection to broker failed!");
        }

        $this->lastActivity = time();

        return $this;
    }

    /**
     * @return $this
     */
    protected function ping()
    {
        $head = chr(0xc0) . chr(0x00);
        $this->socket->write($head, 2);

        return $this;
    }

    /**
     * @return $this
     */
    public function disconnect()
    {
        $head = chr(0xe0) . chr(0x00);
        $this->socket->write($head, 2);
        return $this;
    }

    /**
     * @return $this
     */
    public function close()
    {
        $this->disconnect();
        $this->socket->shutdown();
        return $this;
    }
}