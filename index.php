<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use \Twig\Loader\FilesystemLoader;
use Blog\PostMapper;

require __DIR__ . '/vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('Templates');
$view = new \Twig\Environment($loader);

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

$postMapper = new PostMapper($connection);

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) use ($view) {
    $body = $view ->render('index.twig');
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

$app->get('/{url_key}', function (Request $request, Response $response, $args) use ($view, $postMapper) {
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
