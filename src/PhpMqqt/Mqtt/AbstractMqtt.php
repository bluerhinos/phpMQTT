<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 14/11/2017
 * Time: 22:13
 */

namespace PhpMqqt\Mqtt;


use PhpMqqt\Content\Payload;
use PhpMqqt\Content\VariableHeader;
use PhpMqqt\Mqtt\Socket\Socket;
use PhpMqqt\Mqtt\Will\Will;

/**
 * Class AbstractMqtt
 * @package PhpMqqt\Mqtt
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
    }

    /**
     *
     */
    protected function connect()
    {
        $this->socket->initSocket();

        $this->sendConnectPacket();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function sendConnectPacket()
    {
//        $head = new VariableHeader();
//
//        $head->push($this->clean ? 2 : 0);
//
//        if ($this->will) {
//            $head->push(4);
//            $head->push($this->will->qos() << 3);
//            if ($this->will->retain()) {
//                $head->push(32);
//            }
//        }
//
//        $head->push($this->user ? 128 : 0, 0);
//        $head->push($this->pass ? 64 : 0, 0);
//
//        $load = new Payload();
//        $load->push([chr(0x00), chr(0x06), chr(0x4d), chr(0x51), chr(0x49), chr(0x73), chr(0x64), chr(0x70), chr(0x03)]);
//
//        $load->push(chr($head->getContent()));
//
//
//        $load->push([
//            chr($this->keepAlive >> 8),
//            chr($this->keepAlive & 0xff)
//        ])->convertPush($this->clientId);
//
//        if ($this->will) {
//            dd('here');
//            $load->convertPush([
//                $this->will->topic(),
//                $this->will->content()
//            ]);
//        }
//
//        if ($this->user) {
//            dd('here2');
//
//            $load->convertPush($this->user);
//        }
//
//        if ($this->pass) {
//            dd('here3');
//            $load->convertPush($this->pass);
//        }
//
//
//        $loadHeader = chr(0x10) . chr($load->getLength());

//        fwrite($this->socket->socket,$loadHeader,2);
//        fwrite($this->socket->socket,$load->getContent());

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

        if ($this->user || $this->pass) {
            $payload->convertPush([
                $this->user,
                $this->pass
            ]);

        }

        $payloadHeader = chr(0x10) . chr($payload->getLength());
//        dd($payloadHeader);
        fwrite($this->socket->socket, $payloadHeader, 2);

        fwrite($this->socket->socket, $payload->getContent());

//        $this->socket->write($loadHeader, 2)
//            ->write($load->getContent());


        $response = $this->socket->read(4);

        if (!(ord($response{0}) >> 4 == 2 && $response{3} == chr(0))) {
            var_dump('here');

            throw new \Exception("Connection to broker failed!");
        }

        $this->lastActivity = time();

        return $this;
    }
}