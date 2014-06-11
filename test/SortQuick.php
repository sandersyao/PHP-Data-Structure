<?php
define('STRUCTURE', 'algorithm');
include 'init.inc.php';

$src    = new Sort_SRC;
$max    = (int) (0x100000000 / 2 - 1);
$min    = 0;
$count  = 100000;

for ($offset = 0;$offset < $count;$offset ++) {

    $src[]  = mt_rand($min, $max);
}

echo    "test data ready.\n";

$time   = microtime(true);

Sort_Quick::exec($src);

$wall   = microtime(true) - $time;

for ($offset = 0;$offset < count($src);$offset ++) {

    echo "$offset: ";
    var_dump($src[$offset]);
}

echo    "sort wall time = " . $wall . "\n";