<?php

namespace Blog\Route;

use Blog\DataBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class PersonalCabinet
{

    /**
     * @var Environment
     */
    private Environment $view;

    private DataBase $dataBase;

    public function __construct(Environment $view, DataBase $dataBase)
    {
        $this->view = $view;
        $this->dataBase = $dataBase;
    }

    // Отрисовка Личногокабинета
    public function showPersonalCabinet($connect, Response $response): Response
    {
        error_log('Session is ' . json_encode($_SESSION));
//        if (!isset($user) || !$user['id']) {
//            return $response->withStatus(301)->withHeader('Location', '/user/login');
//        }
//
        $body = $this->view->render('Navigation/PersonalCabinet.twig', [
            'user' => $_SESSION['user']
        ]);
        $response->getBody()->write($body);
        return $response;
        // Конец отрисовки
    }

    public function showPostEditor($connect, Response $response, array $args): Response
    {
        $title = (int)$args['title'];

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * FROM post where title = :title'
        );

        $statement->execute([
//            'title' => $title
'title' => 'Hello World'
        ]);

        $title = $statement->fetchAll();


        error_log('Session is ' . json_encode($_SESSION));
//        if (!isset($user) || !$user['id']) {
//            return $response->withStatus(301)->withHeader('Location', '/user/login');
//        }
//
        $body = $this->view->render('Navigation/PostEditor.twig', [
            'user' => $_SESSION['user'],
            'posts' => $title
        ]);
        $response->getBody()->write($body);
        return $response;
        // Конец отрисовки
    }


//    public function getRole(Request $request, Response $response, array $args): Response
//    {
//        error_log('Распределение ролей');
//        $args = [];
//        $args['User'] = [0];
//        $args['admin'] = [1];
//        $args['helper'] = [2];
//        $connection = $this->dataBase->getConnection();
//        $statement = $connection->prepare(
//            ' SELECT * FROM user WHERE role = 0'
//        );
//        $statement->execute();
//
//        $role = $statement->fetchAll();
//
//        return $response;
//    }
}