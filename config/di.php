<?php

use Blog\DataBase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use function DI\autowire;
use function DI\get;

$host = getenv("DATABASE_HOST");

if ($host == null) {
    $host = "localhost";
}

$dsn = "mysql:host=" . $host . ";dbname=" . getenv("DATABASE_NAME") . ";charset=utf8mb4";

return [
    FilesystemLoader::class => autowire()
        ->constructorParameter('paths', 'Templates'),

    Environment::class => autowire()
        ->constructorParameter('loader', get(FilesystemLoader::class)),

    DataBase::class => autowire()
        ->constructorParameter('dsn', $dsn)
        ->constructorParameter('username', getenv('DATABASE_USERNAME'))
        ->constructorParameter('password', getenv('DATABASE_PASSWORD'))
];
