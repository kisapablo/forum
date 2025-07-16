<?php

use Blog\Route\AboutPage;
use Blog\Route\UserController;
use Blog\Route\PostsController;
use Blog\Route\PersonalCabinet;
use Blog\Slim\TwigMiddleware;
use DI\ContainerBuilder;
use PhpDevCommunity\DotEnv;
use Slim\Factory\AppFactory;
use Twig\Environment;

require __DIR__ . '/vendor/autoload.php';

$builder = new ContainerBuilder(); // dependency container,контейнер зависимостей
$builder->addDefinitions('config/di.php'); // dependency container,контейнер зависимостей
(new DotEnv(__DIR__ . '/.env'))->load(); // dependency container,контейнер зависимостей
$container = $builder->build(); // dependency container, контейнер зависимостей

AppFactory::setContainer($container); // dependency container, контейнер зависимостей

// app
$app = AppFactory::create();

$view = $container->get(Environment::class); // Временной контейнер зависимости/dependency container
$app->add(new TwigMiddleware($view));

$app->get('/user', PersonalCabinet::class);

$app->get('/user/login', [UserController::class, 'showUserLoginPage']);
$app->get('/user/registration', [UserController::class, 'showUserRegistrationPage']);

$app->post('/user/login', [UserController::class, 'authorizeUser']);
$app->post('/user/registration', [UserController::class, 'registerUser']);

$app->get('/about', AboutPage::class);
$app->get('/', [PostsController::class, 'showAllPosts']);
$app->get('/posts', [PostsController::class, 'showAllPosts']);
$app->get('/posts/builder', [PostsController::class, 'showPostBuilderPage']);
$app->get('/posts/{post_id}', [PostsController::class, 'showPostPage']);

$app->post('/posts', [PostsController::class, 'createNewPost']);



$app->run();
