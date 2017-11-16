<?php
/**
 * Created by PhpStorm.
 * User: tikagnus
 * Date: 14/11/2017
 * Time: 22:50
 */

namespace MqqtPhp\Mqtt\Publish;

use MqqtPhp\Content\Payload;
use MqqtPhp\Mqtt\AbstractMqtt;

/**
 * Class Publish
 * @package MqqtPhp\Mqtt
 */
class Publish extends AbstractMqtt
{
    /**
     * @param Topic $topic
     * @param string $content
     * @param int $qos
     * @param int $retain
     * @return $this
     */
    public function publish(Topic $topic, string $content, int $qos = 0, int $retain = 0)
    {
        $load = new Payload();
        $load->convertPush($topic->name());

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