<?php
/**
 * Created by PhpStorm.
 * User: tkagnus
 * Date: 13/11/2017
 * Time: 22:45
 */

error_reporting(0);

require_once "../../vendor/autoload.php";

class Subscribe
{
    public function __construct()
    {
        $x = new \PhpMqqt\PhpMqqt\PhpMqqt('x','y','x');
    }
}


new Subscribe();
