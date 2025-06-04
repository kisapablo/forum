<?php

use Blog\DataBase;
// use Blog\LatestPosts;
use Blog\Route\AboutPage;
use Blog\Route\HomePage;
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
// alt
//$app->get('/about', function (Request $request, Response $response, $args) use ($view) { // отрисовка на /about
//    $body = $view->render('about.twig', [
//        'name' => 'Nikita',
//        'animals' => 'Dogs'
//    ]);
//    $response->getBody()->write($body);
//    return $response;
//});
// конец стартовой отрисовки alt
// Начало стартовой отрисовки находящейся по адресу Route\AboutPage;
$app->get('/about', AboutPage::class);

$app->get('/blog[/{page}]', function (Request $request, Response $response, $args) use ($view, $connection) {
    $postMapper = new PostMapper($connection);

    $page = isset($args['page']) ? (int) $args['page'] : 1;
    $limit = 2; // Отрисовка страниц на /blog(Лимит отрисовки страниц на одной страницы /blog)

    $posts = $postMapper->getList($page, $limit, 'DESC');

    $totalCount = $postMapper->getTotalCount();
    $body = $view->render('blog.twig', [
        'posts' => $posts,
        'pagination' => [
            'current' => $page,
            'paging' => ceil($totalCount / $limit)
        ]
    ]);
    $response->getBody()->write($body);
    return $response;
});

$app->get('/{url_key}', function (Request $request, Response $response, $args) use ($view,$connection) {
    $postMapper = new PostMapper($connection);

    $post = $postMapper->getByUrlKey((string) $args['url_key']);

    if (empty($post)) {
        $body = $view->render('not-found.twig');
    } else {
    $body = $view ->render('post.twig',  [
        'post' => $post
    ]);
    }
    $response->getBody()->write($body);
    return $response;
});

$app->run();
