<?php
/**
 * Created by PhpStorm.
 * User: erest
 * Date: 12.03.2019
 * Time: 04:09
 */

require '../vendor/autoload.php';

class mongo_db
{
    public $collection;
    public $client;

    function __construct($collection) {
        $this->client = new MongoDB\Client(
            'mongodb+srv://list412:List_412@parser-data-gammo.mongodb.net/test?retryWrites=true');

        $this->collection = $this->client->parser->{$collection};
    }

    public function add($data) {
        foreach ($data as &$item) {
            $res = $this->collection->findOne(['number' => $item['number']]);
            if ($res) $this->collection->updateOne(['number' => $item['number']], ['$set' => $item]);
            else $this->collection->insertOne($item);
        }
    }
}