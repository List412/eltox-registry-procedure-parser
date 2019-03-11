<?php
/**
 * Created by PhpStorm.
 * User: erest
 * Date: 12.03.2019
 * Time: 04:09
 */

class mongo_db
{
    public $db;
    public $client;

    function __construct()
    {
        $this->client = new MongoDB\Client(
            'mongodb+srv://list412:List_412@parser-data-gammo.mongodb.net/test?retryWrites=true');

        $this->db = $this->client->test;
    }
}