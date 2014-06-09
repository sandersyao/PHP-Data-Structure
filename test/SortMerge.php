<?php
define('STRUCTURE', 'algorithm');
include 'init.inc.php';

$src    = new Sort_SRC;
$max    = (int) (0x100000000 / 2 - 1);
$min    = 0;
$count  = 10000;

for ($offset = 0;$offset < $count;$offset ++) {

    $src[]  = mt_rand($min, $max);
}

Sort_Merge::exec($src);

for ($offset = 0;$offset < count($src);$offset ++) {

    echo "$offset: ";
    var_dump($src[$offset]);
}