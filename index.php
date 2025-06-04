<?php

use Blog\DataBase;
// use Blog\LatestPosts;
use Blog\Route\AboutPage;
use Blog\Route\BlogPage;
use Blog\Route\HomePage;
use Blog\Route\PostPage;
use Blog\Slim\TwigMiddleware;
use DI\ContainerBuilder;
use PhpDevCommunity\DotEnv;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Twig\Environment;
use \Twig\Loader\FilesystemLoader;
use Blog\PostMapper;

require __DIR__ . '/vendor/autoload.php';

//$loader = new FilesystemLoader('Templates'); //alt dependency container, контейнер зависимостей
//$view = new Environment($loader); //alt dependency container, контейнер зависимостей

$builder = new ContainerBuilder(); // dependency container,контейнер зависимостей
$builder->addDefinitions('config/di.php'); // dependency container,контейнер зависимостей
(new DotEnv(__DIR__ .  '/.env'))->load(); // dependency container,контейнер зависимостей

$container = $builder->build(); // dependency container, контейнер зависимостей

AppFactory::setContainer($container); // dependency container, контейнер зависимостей

// app
$app = AppFactory::create();

$view = $container->get(Environment::class); // Временной контейнер зависимости/dependency container
$app->add(new TwigMiddleware($view));

$connection = $container->get(DataBase::class)->getConnection();


        // alt
//$app->get('/', function (Request $request, Response $response) use ($view, $connection) { // Стартовая отрисовка
//    $latestPosts = new LatestPosts($connection);
//    $posts = $latestPosts->get(3); // Отрисовка постов на главной странице(С параметром в последние три поста)
//
//    $body = $view->render('index.twig', [
//        'posts' => $posts
//    ]);
//    $response->getBody()->write($body);
//    return $response;
//}); // конец стартовой отрисовки alt

// Начало стартовой отрисовки находящейся по адресу Route\HomePage;
$app->get('/',HomePage::class . ':execute'); // конец стартовой отрисовки
// Начало стартовой отрисовки находящейся по адресу Route\AboutPage;
$app->get('/about', AboutPage::class); // конец стартовой отрисовки
// Начало стартовой отрисовки находящейся по адресу Route\BlogPage;
$app->get('/blog[/{page}]', BlogPage::class); // конец стартовой отрисовки
$app->get('/{url_key}', PostPage::class);
// alt post
//$app->get('/{url_key}', function (Request $request, Response $response, $args) use ($view,$connection) {
//    $postMapper = new PostMapper($connection);
//
//    $post = $postMapper->getByUrlKey((string) $args['url_key']);
//
//    if (empty($post)) {
//        $body = $view->render('not-found.twig');
//    } else {
//    $body = $view ->render('post.twig',  [
//        'post' => $post
//    ]);
//    }
//    $response->getBody()->write($body);
//    return $response;
//});
// end alt post

$app->run();
