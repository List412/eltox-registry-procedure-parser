<?php
/**
 * Created by PhpStorm.
 * User: list412
 * Date: 16.06.18
 * Time: 0:20
 */

$baseURL = "https://eltox.ru/registry/procedure";

$content = file_get_contents($baseURL);

echo  $content;