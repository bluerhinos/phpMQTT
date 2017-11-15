<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 15/11/2017
 * Time: 21:37
 */

namespace PhpMqqt\Mqtt\Subscribe;


/**
 * Class Topic
 * @package PhpMqqt\Mqtt\Subscribe
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
     * @var callable
     */
    protected $callable;

    /**
     * Topic constructor.
     * @param string $name
     * @param int $qos
     * @param callable $callable
     */
    public function __construct(string $name, int $qos = 0,$callable)
    {
        $this->name = $name;
        $this->qos = $qos;
        $this->callable = $callable;
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

    /**
     * @return callable
     */
    public function callable()
    {
        return $this->callable;
    }

}