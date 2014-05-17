<?php

define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/../');

if (!defined('STRUCTURE')) {

    define('STRUCTURE', 'B-Tree');
}

spl_autoload_register(function ($className) {

    $path   = ROOT_PATH . STRUCTURE . '/' . str_replace('_', '/', $className) . '.class.php';

    if (is_file($path)) {

        include $path;
    }
});