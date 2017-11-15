<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 14/11/2017
 * Time: 22:50
 */

namespace PhpMqqt\Mqtt\Publish;

use PhpMqqt\Content\Payload;
use PhpMqqt\Mqtt\AbstractMqtt;

/**
 * Class Publish
 * @package PhpMqqt\Mqtt
 */
class Publish extends AbstractMqtt
{
    /**
     * @param string $topic
     * @param string $content
     * @param int $qos
     * @param int $retain
     * @return $this
     */
    public function publish(string $topic, string $content, int $qos = 0, int $retain = 0)
    {
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