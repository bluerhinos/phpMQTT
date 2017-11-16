<?php
/**
 * Created by PhpStorm.
 * User: tikagnus
 * Date: 15/11/2017
 * Time: 21:37
 */

namespace MqqtPhp\Mqtt\Publish;


/**
 * Class Topic
 * @package MqqtPhp\Mqtt\Publish
 */
class Topic
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var int
     */
    protected $qos = 0;

    /**
     * Topic constructor.
     * @param string $name
     * @param int $qos
     */
    public function __construct(string $name, int $qos = 0)
    {
        $this->name = $name;
        $this->qos = $qos;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function qos()
    {
        return $this->qos;
    }
}