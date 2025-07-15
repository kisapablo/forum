<?php


namespace blog\config;
use mysqli;

class DBTest
{
//$dbparam = [
//    'dsn' => 'mysql:host=localhost;dbname=blog_php',
//    'username' => 'root',
//    'password' => 'root',
//];


    function getParam(): array
    {
        $dsn = [
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'blog_php',
        ];
        $username = [
            'DB_USER' => 'root',
        ];
        $password = [
            'DB_PASS' => 'root'
        ];
        return array_merge($dsn, $username, $password);

    }


// Определяем константу, используя значение переменной $config

    function getDB(): mysqli|bool
    {
        $params = $this->getParam();
        return mysqli_connect(
            $params['DB_HOST'],
            $params['DB_NAME'],
            $params['DB_PASS'],
            $params['DB_USER']

        );

    }
}