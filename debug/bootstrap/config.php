<?php

$debug = false;

if ($debug) {
    return [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'tuntun',
       // 'database' => 'future',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => '',
    ];
}

return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'tuntun',
    'username' => 'root',
    'password' => 'pA^-!!?G2->J24hJ',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
];