<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 14/11/2017
 * Time: 22:50
 */

namespace PhpMqqt\Mqtt;


use PhpMqqt\Content\Payload;

/**
 * Class Publish
 * @package PhpMqqt\Mqtt
 */
class Publish extends AbstractMqtt
{
    /**
     * @var int
     */
    protected $messageId = 0;

    /**
     * @param string $topic
     * @param string $content
     * @param int $qos
     * @param int $retain
     * @return $this
     */
    public function publish(string $topic, string $content, int $qos = 0, int $retain = 0)
    {
        $this->connect();

        $load = new Payload();
        $load->convertPush($topic);

        if ($qos) {
            $id = $this->messageId++;
            $load->push($id >> 8)
                ->push($id % 256);
        }

        $load->push($content, strlen($content));

        $cmd = 0x30;

        if ($qos) {
            $cmd += $qos << 1;
        }

        if ($retain) {
            $cmd += 1;
        }

        $head = " ";
        $head{0} = chr($cmd);
        $head .= $load->getLengthConverted();

        $this->socket->write($head, strlen($head))
            ->write($load->getContent(), $load->getLength());

        return $this;
    }
}