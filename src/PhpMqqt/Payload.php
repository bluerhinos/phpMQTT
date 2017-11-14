<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 13/11/2017
 * Time: 23:52
 */

namespace PhpMqqt\PhpMqqt;


class Payload
{
    protected $buff = "";
    protected $count = 0;

    public function __construct($items = null,$increment = 1)
    {
        if ($items) {
            $this->push($items,$increment);
        }
    }

    public function push($items, $increment = 1)
    {
        if (is_array($items)) {
            foreach ($items as $item) {
                $this->buff .= $item;
                $this->count += $increment;
            }
            return $this;
        }

        $this->buff .= $items;
        $this->count += $increment;

        return $this;
    }

    public function pushToBytes($str)
    {
        $len = strlen($str);
        $msb = $len >> 8;
        $lsb = $len % 256;
        $ret = chr($msb);
        $ret .= chr($lsb);
        $ret .= $str;

        return $this->push($ret, $len + 2);
    }


    public
    function count()
    {
        return $this->count;
    }

    public
    function get()
    {
        return $this->buff;
    }
}