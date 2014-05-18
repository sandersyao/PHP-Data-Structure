<?php
define('STRUCTURE', 'B-Tree');
include 'init.inc.php';

$options    = array(
    'number_slots'  => 5,
    'length_key'    => 100,
);
$file       = 'index.btree';
$index      = BTree::open($file, $options);
$command    = $argv[1];

switch ($command) {

    case    'testinsert' :

        $keyList    = array();

        for ($offset = 1;$offset <= 1000; $offset ++) {

            $keyList[]  = $offset;
        }

        shuffle($keyList);
        $offset = 1;

        foreach ($keyList as $key) {

            $index->command(
                'insert',
                array(
                    'key'   => $key,
                    'value' => $key,
                )
            );
            echo "$offset insert seccess: $key \n";
            $offset ++;
        }

        break;

    case    'testdelete' :

        $keyList    = array();

        for ($offset = 1;$offset <= 1000; $offset ++) {

            $keyList[]  = $offset;
        }

        shuffle($keyList);
        $offset = 1;

        foreach ($keyList as $key) {

            $index->command(
                'delete',
                array(
                    'key'   => $key,
                )
            );
            echo "$offset delete seccess: $key \n";
            $offset ++;
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

        $pointer    = (int) $argv[2];

        $index->command(
            $command,
            array(
                'pointer'   => $pointer,
            )
        );
        break;
}

$index  = NULL;



/**
              35   46   71
        27 30  39 43
25 26   28 29    31 32 33 34

                  46    71
        30 35 39 43
26 27 28 29      31 32 33 34
*/
