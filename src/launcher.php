<?php
/**
 * Created by PhpStorm.
 * User: erest
 * Date: 11.03.2019
 * Time: 21:47
 */

require '../vendor/autoload.php';
require 'eltox.php';

// TODO parsing argv
$parser = new eltox();
$parser->parse();