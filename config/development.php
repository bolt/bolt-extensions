<?php
Symfony\Component\Debug\Debug::enable();
$main = include __DIR__."/production.php";
return array_merge($main, [

    'debug' => true,
    'doctrine.proxymode' => 1,
    
    'db'=> [
        'driver'     => 'pdo_mysql',
        'dbname'     => 'bolt_newext',
        'host'       => '127.0.0.1',
        'user'       => 'root',
        'password'   => '',
    ],


]);