<?php

namespace Blog\Route;

use Blog\DataBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;


class PostsController
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

    public function showPostBuilderPage(Request $request, Response $response): Response
    {
        $body = $this->view->render('Navigation/CreateNewPosts.twig');
        $response->getBody()->write($body);
        return $response;
    }

    function showAllPosts(Request $request, Response $response): Response
    {
        $limit = 3;

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * FROM post ORDER BY publication_date DESC LIMIT ' . $limit
        );

        $statement->execute();

        $posts = $statement->fetchAll();

        $body = $this->view->render('index.twig', [
            'posts' => $posts,
            'showAuthButton' => true,
            'showUserInfo' => false,
        ]);

        $response->getBody()->write($body);
        return $response;

    }

    public function showPostPage(Request $request, Response $response, array $args = [])
    {
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $post_id = (int)$args['post_id'];

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * FROM post where id = :id'
        );

        $statement->execute([
            'id' => $post_id
        ]);

        $posts = $statement->fetchAll();

        if (empty($posts)) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $post = $posts[0];

        $body = $this->view->render('post.twig', [
            'post' => $post
        ]);
        $response->getBody()->write($body);

        return $response;
    }

    public function createNewPost(Request $request, Response $response): Response
    {
        $title = $_POST['title'];
        $content = $_POST['content'];

        $connection = $this->dataBase->getConnection();


        // Вбивание данных из шаблонов
        $statement = $connection->prepare(
            "INSERT INTO post (title, content, publication_date) 
                VALUES ('$title', '1$content',  CURRENT_DATE)"
        );


        if ($statement->execute()) {
            header("Location: /");
        }

        $body = $this->view->render('Navigation/CreateNewPosts.twig');
        $response->getBody()->write($body);
        return $response;
    }
}
