<?php
/**
 * Created by PhpStorm.
 * User: list412
 * Date: 16.06.18
 * Time: 1:20
 */

class Procedure
{
    public $id;
    public $oos;
    public $link;
    public $email;
    public $document;

    public function __construct($id, $oos, $link, $email, $document)
    {
        $this->id=$id;
        $this->oos=$oos;
        $this->link=$link;
        $this->document=$document;
    }
}