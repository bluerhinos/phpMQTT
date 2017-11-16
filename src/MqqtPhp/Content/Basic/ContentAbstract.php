<?php
/**
 * Created by PhpStorm.
 * User: tikagnus
 * Date: 14/11/2017
 * Time: 20:34
 */

namespace MqqtPhp\Content\Basic;

/**
 * Class ContentAbstract
 * @package MqqtPhp\Content\Basic
 */
class ContentAbstract implements ContentInterface
{
    CONST OPERATOR = '+';

    /**
     * @var string
     */
    protected $content = "";
    /**
     * @var int
     */
    protected $length = 0;

    /**
     * @param array|string $items
     * @param int $increment
     * @return $this
     */
    public function push($items, int $increment = 1)
    {
        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $this->operate($item);
            $this->length += $increment;
        }

        return $this;
    }

    /**
     * @param array|string $items
     * @return $this
     */
    public function convertPush($items)
    {
        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $this->convert($item);
        }

        return $this;
    }

    /**
     * @param string $item
     * @return $this
     */
    protected function convert($item)
    {
        $len = strlen($item);
        $msb = $len >> 8;
        $lsb = $len % 256;
        $ret = chr($msb);
        $ret .= chr($lsb);
        $ret .= $item;

        return $this->push($ret, $len + 2);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return string
     */
    public function getLengthConverted()
    {
        $buff = "";
        do {
            $digit = $this->length % 128;
            $len = $this->length >> 7;
            if ($len > 0)
                $digit = ($digit | 0x80);
            $buff .= chr($digit);
        } while ($len > 0);

        return $buff;
    }

    /**
     * @param string|int|float|double $item
     * @return $this
     */
    protected function operate($item)
    {
        switch (static::OPERATOR) {
            case '+':
                $this->content += $item;
                break;
            case '.':
                $this->content .= $item;
            default:

        }

        return $this;
    }

}