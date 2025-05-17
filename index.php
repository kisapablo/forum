<?php

use Blog\LatestPosts;
use Blog\Twig\AssetExtension;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use \Twig\Loader\FilesystemLoader;
use Blog\PostMapper;

require __DIR__ . '/vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('Templates');
$view = new \Twig\Environment($loader);

$view->addExtension(new AssetExtension());

$config = include 'config/database.php'; //mysql
$dsn = $config['dsn']; //mysql
$username = $config['username']; //mysql
$password = $config['password']; //mysql

try{
    $connection = new PDO($dsn, $username, $password); // mysql
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //mysql
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); //mysql
} catch (PDOException $exception) {   // mysql
    echo 'Database Error: ' . $exception-> getMessage();  // mysql
    exit;  // mysql
}


$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response) use ($view, $connection) {
    $latestPosts = new LatestPosts($connection);
    $posts = $latestPosts->get(3); // Отрисовка постов на главной странице(С параметром в последние три поста)

    $body = $view->render('index.twig', [
        'posts' => $posts
    ]);
    $response->getBody()->write($body);
    return $response;
});

$app->get('/about', function (Request $request, Response $response, $args) use ($view) {
    $body = $view->render('about.twig', [
            'name' => 'Nikita',
            'animals' => 'Dogs'
        ]);
    $response->getBody()->write($body);
    return $response;
});

$app->get('/blog[/{page}]', function (Request $request, Response $response, $args) use ($view, $connection) {
    $latestPosts = new PostMapper($connection);

    $page = isset($args['page']) ? (int) $args['page'] : 1;
    $limit = 2; // Отрисовка страниц на /blog(Лимит отрисовки страниц на одной страницы /blog)

    $posts = $latestPosts->getList($page, $limit, 'DESC');

    $body = $view->render('blog.twig', [
        'posts' => $posts
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
