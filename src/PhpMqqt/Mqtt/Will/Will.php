<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 14/11/2017
 * Time: 20:11
 */

namespace PhpMqqt\Mqtt\Will;


class Will
{
    protected $qos;
    protected $retain;
    protected $topic;
    protected $content;


    public function qos()
    {
        return $this->qos();
    }

    public function retain()
    {
        return $this->retain;
    }

    public function topic()
    {
        return $this->topic;
    }

    public function content()
    {
        return $this->content;
    }

}