<?php
/**
 * Created by PhpStorm.
 * User: tikagnus
 * Date: 14/11/2017
 * Time: 22:50
 */

namespace MqqtPhp\Mqtt\Subscribe;

use MqqtPhp\Content\Payload;
use MqqtPhp\Mqtt\AbstractMqtt;

/**
 * Class Publish
 * @package MqqtPhp\Mqtt
 */
class Subscribe extends AbstractMqtt
{
    /**
     * @var int
     */
    protected $qos = 0;

    /**
     * @var array
     */
    protected $topics = [];

    /**
     * @param int $qos
     * @return $this
     */
    public function qos(int $qos = 0)
    {
        $this->qos = $qos;
        return $this;
    }

    /**
     * @param Topic $topic
     * @return $this
     */
    public function topic(Topic $topic)
    {
        $this->topics[] = $topic;
        return $this;
    }

    /**
     * @param Topic[]|null $topics
     * @return $this
     */
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

        $cmd = 0x80 + (($this->qos ?? 0) << 1);
        $head = chr($cmd) . chr($load->getLength());

        $this->socket->write($head, 2);
        $this->socket->write($load->getContent(), $load->getLength());

        $response = $this->socket->read(2);
        $bytes = ord(substr($response, 1, 1));
        $this->socket->read($bytes);

        return $this;
    }

    /**
     * @return $this
     */
    public function listen()
    {
        while (1) {

            $exit = $this->loop();

            if ($exit) {
                return $this;
            }
        }
    }

    /**
     * @return bool
     */
    protected function loop()
    {
        $breakLoop = false;
        if ($this->socket->end()) {

            $this->clean = false;
            $this->socket->close();
            $this->autoReconnect()
                ->subscribeTopics();
        }

        $byte = $this->socket->read(1, true);

        if (strlen($byte)) {

            $cmd = (int)(ord($byte) / 16);

            $message = null;
            $length = $this->socket->getBufferLength();
            if ($length) {
                $message = $this->socket->read($length);
            }

            if ($cmd) {
                if ($message) {
                    $breakLoop = $this->parseMessage($message, $cmd) === false;
                }

                $this->updateLastActivity();
            }

        } else {
            usleep(100000);
        }


        $this->checkLastActivity();

        return $breakLoop;
    }

    /**
     * @param string $message
     * @param string $cmd
     * @return bool
     */
    protected function parseMessage(string $message, string $cmd)
    {
        $breakLoop = false;
        switch ($cmd) {
            case 3:
                $breakLoop = $this->dispatchMessage($message) == false;
                break;
        }

        $this->updateLastActivity();

        return $breakLoop;
    }

    /**
     * @return $this
     */
    protected function updateLastActivity()
    {
        $this->lastActivity = time();
        return $this;
    }

    /**
     * @param string $message
     * @return bool
     */
    protected function dispatchMessage(string $message)
    {
        $topicLength = (ord($message{0}) << 8) + ord($message{1});
        $topicName = substr($message, 2, $topicLength);
        $message = substr($message, ($topicLength + 2));

        $break = false;

        foreach ($this->topics as $realTopicName => $topic) {
            if (preg_match("/^" . str_replace("#", ".*",
                    str_replace("+", "[^\/]*",
                        str_replace("/", "\/",
                            str_replace("$", '\$',
                                $realTopicName)))) . "$/", $topicName)) {

                if ($topic->callable()) {
                    $exit = call_user_func($topic->callable(), $topic, $message);
                }

                if ($exit === false) {
                    $break = true;
                }

            }
        }

        return $break;
    }

    /**
     * @return $this
     */
    protected function checkLastActivity()
    {
        if ($this->lastActivity < (time() - $this->keepAlive)) {
            $this->ping();
        }

        if ($this->lastActivity < time() - ($this->keepAlive * 2)) {
            $this->clean = false;
            $this->socket->close();
            $this->autoReconnect()
                ->subscribeTopics();
        }

        return $this;
    }

    /**
     * @param array $topics
     * @return $this
     */
    protected function addTopics(array $topics)
    {
        foreach ($topics as $topic) {
            $this->topics[$topic->name()] = $topic;
        }
        return $this;
    }


}