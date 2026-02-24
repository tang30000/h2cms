<?php
return [
    'db' => [
        'dsn'      => 'mysql:host=localhost;dbname=h2cms;charset=utf8mb4',
        'user'     => 'root',
        'password' => 'usbw',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],
    'default' => [
        'a' => 'home',
        'b' => 'index',
        'c' => 'index',
    ],
    'debug' => true,
    'cache' => [
        'driver' => 'file',
        'prefix' => 'cms_',
    ],
    'queue' => [
        'driver'       => 'database',
        'max_attempts' => 3,
    ],
    'middleware' => [],
    'mail' => [
        'host'     => 'smtp.qq.com',
        'port'     => 465,
        'user'     => '',
        'password' => '',
        'name'     => 'H2CMS',
        'ssl'      => true,
    ],
];
