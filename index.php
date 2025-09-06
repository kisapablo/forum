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
session_cache_limiter(false); // Disable cache limiter
session_start();

$builder = new ContainerBuilder(); // dependency container,контейнер зависимостей
$builder->addDefinitions('config/di.php'); // dependency container,контейнер зависимостей
(new DotEnv(__DIR__ . '/.env'))->load(); // dependency container,контейнер зависимостей
$container = $builder->build(); // dependency container, контейнер зависимостей

AppFactory::setContainer($container); // dependency container, контейнер зависимостей

// app
$app = AppFactory::create();

$view = $container->get(Environment::class); // Временной контейнер зависимости/dependency container
$app->add(new TwigMiddleware($view));

// Routing
$app->get('/admin', [PersonalCabinet::class, 'showPersonalCabinet']);
$app->get('/user', [PersonalCabinet::class, 'showPersonalCabinet']);
$app->get('/user/login', [UserController::class, 'showUserLoginPage']);
$app->get('/user/registration', [UserController::class, 'showUserRegistrationPage']);
$app->get('/user/logout', [UserController::class, 'DeleteSession']);
$app->post('/user/login', [UserController::class, 'authorizeUser']);
$app->post('/user/registration', [UserController::class, 'registerUser']);


$app->get('/about', AboutPage::class);
$app->get('/', [PostsController::class, 'showAllPosts']);
$app->get('/posts/delete/{post_id}', [PostsController::class, 'showDeletePosts']);
$app->get('/posts', [PostsController::class, 'showAllPosts']);
//$app->get('/posts/all[/{page}]', [PostsController::class, 'showAllPosts']);
$app->get('/posts/builders', [PostsController::class, 'showPostBuilderPage']);
$app->get('/posts/leaders', [PostsController::class, 'showLeaderKarma']);
$app->post('/posts/{post_id}/comments', [PostsController::class, 'createNewPostComment']);
$app->get('/user/posts/PostEditor/{post_id}', [PostsController::class, 'getPostInfo']);
$app->get('/user/posts/CommentEditor/{post_id}/{comment_id}', [PostsController::class, 'getCommentInfo']);
//$app->get('/search/posts/test', [PostsController::class, 'getResultSearch']);
//$app->get('/search/users/test', [PostsController::class, 'getResultSearch']);
$app->get('/posts/{post_id}', [PostsController::class, 'showPostPage']);
//$app->get('/posts/old/blog', [PostsController::class, 'getallOldPage']); // Заморожено, реабилитация страницы не эффективна

$app->post('/posts', [PostsController::class, 'createNewPost']);
$app->post('/user/posts/PostEditor/{post_id}', [PostsController::class, 'updatePost']);
$app->post('/user/posts/CommentEditor/{post_id}/{comment_id}', [PostsController::class, 'updateComment']);
$app->post('/posts/delete/Deleting', [PostsController::class, 'DeletePost']);
$app->get('/user/UserEditor', [PersonalCabinet::class, 'showUserEditor']);
$app->post('/user/UserEditor/debug', [PersonalCabinet::class, 'addUserIco']);
$app->post('/debug', [PersonalCabinet::class, 'getGlobalVariable']);
$app->post('/user/icons/debug', [PersonalCabinet::class, 'getSelectedDefaultIco']);
//$app->get('/user/posts/select', [PersonalCabinet::class, 'showPublishedPosts']);
$app->get('/user/icons/default', [PersonalCabinet::class, 'showDefaultIconsSelect']);
// end routing

$app->run();
