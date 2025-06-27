<?php

use Blog\DataBase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use function DI\autowire;
use function DI\get;

return [
    FilesystemLoader::class => autowire()
    ->constructorParameter('paths', 'Templates'),

    Environment::class => autowire()
    ->constructorParameter('loader', get(FilesystemLoader::class)),

    DataBase::class => autowire()
    ->constructorParameter('connection', get(PDO::class)),

    PDO::class => autowire()
        ->constructorParameter('dsn', getenv('DATABASE_DSN'))
        ->constructorParameter('username', getenv('DATABASE_USERNAME'))
        ->constructorParameter('passwd', getenv('DATABASE_PASSWORD'))
        ->constructorParameter('options', []),
];

/* alt DataBase::class
    DataBase::class => autowire()
    ->constructorParameter('dsn', getenv('DATABASE_DSN'))
    ->constructorParameter('username', getenv('DATABASE_USERNAME'))
    ->constructorParameter('password', getenv('DATABASE_PASSWORD')),
*/