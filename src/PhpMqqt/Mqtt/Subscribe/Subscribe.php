<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 14/11/2017
 * Time: 22:50
 */

namespace PhpMqqt\Mqtt\Subscribe;

use PhpMqqt\Content\Payload;
use PhpMqqt\Mqtt\AbstractMqtt;

/**
 * Class Publish
 * @package PhpMqqt\Mqtt
 */
class Subscribe extends AbstractMqtt
{

    protected $topics = [];

    public function topic(Topic $topic)
    {
        $this->topics[] = $topic;
        return $this;
    }

    public function subscribeTopics($topics = null)
    {
        if (!is_array($topics)) {
            $topics = is_null($topics) ? [] : [$topics];
        }

        if (count($topics)) {
            $this->addTopics($topics);
        }

        $load = new Payload();
        $load->push([chr($this->messageId >> 8),
            chr($this->messageId % 256)]);

        foreach ($this->topics as $topic) {
            $load->convertPush($topic->name())
                ->push(chr($topic->qos()));
        }

        $cmd = 0x80 + (($topic->qos ?? 0) << 1);
        $head = chr($cmd) . chr($load->getLength());

        $this->socket->write($head, 2);
        $this->socket->write($load->getContent(), $load->getLength());

        $response = $this->socket->read(2);
        $bytes = ord(substr($response, 1, 1));
        $response = $this->socket->read($bytes);
    }

    public function listen()
    {
        while (1) {
            $this->loop();
        }
    }

    protected function loop()
    {
        if ($this->socket->end()) {
            var_dump('end in loop');
            $this->clean = false;
            $this->socket->close();
            $this->autoReconnect()
                ->subscribe();
        }

        $byte = $this->socket->read(1, true);

        if (strlen($byte)) {

            $cmd = (int)(ord($byte) / 16);

            $multiplier = 1;
            $length = 0;
            do {
                $digit = ord($this->socket->read(1));
                $length += ($digit & 127) * $multiplier;
                $multiplier *= 128;
            } while (($digit & 128) != 0);

            $message = null;

            if ($length) {
                $message = $this->socket->read($length);
            }

            if ($cmd) {
                if ($message) {
                    $this->parseMessage($message, $cmd);
                }
            }

            $this->updateLastActivity();
        }


        $this->checkLastActivity();
    }

    protected function parseMessage(string $message, string $cmd)
    {
        switch ($cmd) {
            case 3:
                $this->dispatchMessage($message);
                break;
        }
        $this->updateLastActivity();
        return $this;
    }

    protected function updateLastActivity()
    {
        $this->lastActivity = time();
    }

    protected function dispatchMessage(string $message)
    {
        $topicLength = (ord($message{0}) << 8) + ord($message{1});
        $topicName = substr($message, 2, $topicLength);
        $message = substr($message, ($topicLength + 2));

        foreach ($this->topics as $realTopicName => $topic) {
            if (preg_match("/^" . str_replace("#", ".*",
                    str_replace("+", "[^\/]*",
                        str_replace("/", "\/",
                            str_replace("$", '\$',
                                $realTopicName)))) . "$/", $topicName)) {

                call_user_func($topic->callable(), $topic, $message);

            }
        }
    }

    protected function checkLastActivity()
    {
        if ($this->lastActivity < (time() - $this->keepAlive)) {
            var_dump('here1' . uniqid());
            $this->ping();
        }

        if ($this->lastActivity < time() - ($this->keepAlive * 2)) {
            var_dump('here2');
            $this->clean = false;
            $this->socket->close();
            $this->autoReconnect()
                ->subscribe();
        }

        return $this;
    }

    protected function addTopics(array $topics)
    {
        foreach ($topics as $topic) {
            $this->topics[$topic->name()] = $topic;
        }
        return $this;
    }


}