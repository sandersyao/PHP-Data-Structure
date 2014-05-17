<?php
define('STRUCTURE', 'B-Tree');
include 'init.inc.php';

$options    = array(
    'number_slots'  => 3,
    'length_key'    => 100,
);
$file       = 'index.btree';
$index      = BTree::open($file, $options);
$command    = $argv[1];

switch ($command) {

    case    'testinsert' :

        $keyList    = array();

        for ($offset = 1;$offset <= 100; $offset ++) {

            $keyList[]  = $offset;
        }

        shuffle($keyList);

        foreach ($keyList as $key) {

            $index->command(
                'insert',
                array(
                    'key'   => $key,
                    'value' => $key
                )
            );
        }

        break;

    case    'insert' :

        $key    = $argv[2];
        $value  = $argv[3];

        $index->command(
            $command,
            array(
                'key'   => $key,
                'value' => $value,
            )
        );
        break;

    case    'select' :

        $key    = $argv[2];
        $value  = $index->command(
            $command,
            array(
                'key'   => $key,
            )
        );

        var_dump($value);
        break;

    case    'delete' :

        $key    = $argv[2];

        $index->command(
            $command,
            array(
                'key'   => $key,
            )
        );
        break;

    case    'debug' :

        $index->command(
            $command,
            array()
        );
        break;
}

$index  = NULL;
