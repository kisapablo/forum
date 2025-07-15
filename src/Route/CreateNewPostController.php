<?php

namespace Blog\Route;

use PDO;
use Blog\DataBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;


class CreateNewPostController
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

    public function showNewPostPage(Request $request, Response $response)
    {
        $body = $this->view->render('Navigation/CreateNewPosts.twig');
        $response->getBody()->write($body);
        return $response;
    }

    public function createNewPost(Request $request, Response $response)
    {
        $title = $_POST['title'];
        $urlKey = $_POST['url_key'];
        $content = $_POST['content'];
        $description = $_POST['description'];

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "SELECT url_key FROM `post` WHERE url_key = ?"
        );

        $statement->execute([$urlKey]);

        $result = $statement->fetchAll();
        if (count($result) > 0)
            echo 'Пост с уже таким урл ключем уже опубликован!';


        // Вбивание данных из шаблонов
        $statement = $connection->prepare(
            "INSERT INTO post (title, url_key, content, description, published_date) 
                VALUES ('$urlKey', '$content', '$description','$title', CURRENT_DATE)"
        );


        if ($statement->execute()) {
            header("Location: /");
        }
        $body = $this->view->render('Navigation/CreateNewPosts.twig');
        $response->getBody()->write($body);
        return $response;
    }
}
