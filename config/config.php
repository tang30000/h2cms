<?php
use Lib\Env;

return [
    'db' => [
        'dsn'      => 'mysql:host=' . Env::get('DB_HOST', 'localhost')
                    . ';dbname=' . Env::get('DB_NAME', 'h2cms')
                    . ';charset=utf8mb4',
        'user'     => Env::get('DB_USER', 'root'),
        'password' => Env::get('DB_PASS', ''),
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],
    'app_key' => Env::get('APP_KEY', 'h2cms_default_key_change_me_32!!'),
    'default' => [
        'a' => 'home',
        'b' => 'index',
        'c' => 'index',
    ],
    'debug'      => Env::get('APP_DEBUG', true),
    'cache'      => [
        'driver' => 'file',
        'prefix' => 'cms_',
    ],
    'queue'      => [
        'driver'       => 'database',
        'max_attempts' => 3,
    ],
    'middleware'  => [],
    'mail'       => [
        'host'     => Env::get('MAIL_HOST', 'smtp.qq.com'),
        'port'     => (int)Env::get('MAIL_PORT', 465),
        'user'     => Env::get('MAIL_USER', ''),
        'password' => Env::get('MAIL_PASS', ''),
        'name'     => 'H2CMS',
        'ssl'      => true,
    ],
];
