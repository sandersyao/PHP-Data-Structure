<?php
define('STRUCTURE', 'algorithm');
include 'init.inc.php';

$src    = new Sort_SRC;
$max    = (int) (0x100000000 / 2 - 1);
$min    = 0;
$count  = 1000;

for ($offset = 0;$offset < $count;$offset ++) {

    $src[]  = mt_rand($min, $max);
}

Sort_Insertion::exec($src);

for ($offset = 0;$offset < count($src);$offset ++) {

    echo "$offset: ";
    var_dump($src[$offset]);
}