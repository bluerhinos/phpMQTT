<?php
/**
 * Created by PhpStorm.
 * User: tikagnus
 * Date: 14/11/2017
 * Time: 20:11
 */

namespace MqqtPhp\Mqtt\Will;

use MqqtPhp\Mqtt\Subscribe\Topic;

/**
 * Class Will
 * @package MqqtPhp\Mqtt\Will
 */
class Will
{
    /**
     * @var Topic
     */
    protected $topic;
    /**
     * @var string
     */
    protected $content;
    /**
     * @var int
     */
    protected $qos;
    /**
     * @var int
     */
    protected $retain;

    /**
     * Will constructor.
     * @param $topic
     * @param $content
     * @param $qos
     * @param $retain
     */
    public function __construct(Topic $topic, string $content, int $qos, int $retain)
    {
        $this->topic = $topic;
        $this->content = $content;
        $this->qos = $qos;
        $this->retain = $retain;
    }

    /**
     * @return int
     */
    public function qos()
    {
        return $this->qos;
    }

    /**
     * @return int
     */
    public function retain()
    {
        return $this->retain;
    }

    /**
     * @return Topic
     */
    public function topic()
    {
        return $this->topic;
    }

    /**
     * @return string
     */
    public function content()
    {
        return $this->content;
    }

}