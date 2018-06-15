<?php
/**
 * Created by PhpStorm.
 * User: list412
 * Date: 16.06.18
 * Time: 1:26
 */

class Document
{
    public $name;
    public $link;

    public function __construct($name, $link)
    {
        $this->name=$name;
        $this->link=$link;
    }
}