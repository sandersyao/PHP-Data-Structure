<?php
define('STRUCTURE', 'algorithm');
include 'init.inc.php';

$src    = new Sort_SRC;
$src[]  = 5;
$src[]  = 6;
$src[]  = 7;
$src[]  = 8;

$src[0] = 10;
$src[2] = 10;

for ($offset = 0;$offset < count($src);$offset ++) {

    echo "$offset: ";
    var_dump($src[$offset]);
}
