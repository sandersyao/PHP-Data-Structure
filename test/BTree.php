<?php
define('STRUCTURE', 'B-Tree');
include 'init.inc.php';

$options    = array(
    'number_slots'  => 3,
    'length_key'    => 10,
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

            if (false != $index->command('select', array('key'=>$key))) {

                echo "$offset delete failure: $key \n";
                exit;
            }

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

    case    'compare' :

        $key        = $argv[2];
        $operator   = $argv[3];
        $list       = $index->command(
            $command,
            array(
                'key'       => $key,
                'operator'  => $operator,
            )
        );

        if (!($list instanceof Iterator)) {

            var_dump($list);
        }

        foreach ($list as $key => $value) {

            var_dump($key);
        }

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

        $pointer    = isset($argv[2])   ? (int) $argv[2]    : 0;

        $index->command(
            $command,
            array(
                'pointer'   => $pointer,
            )
        );
        break;
}

$index  = NULL;


