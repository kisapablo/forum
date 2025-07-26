<?php

namespace Blog\Route;


use Blog\CommentRepository;
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
    private CommentRepository $commentRepository;
    public function __construct(Environment $view, DataBase $dataBase, CommentRepository $commentRepository)
    {
        $this->view = $view;
        $this->dataBase = $dataBase;
        $this->commentRepository = $commentRepository;
    }

    public function showPostBuilderPage(Request $request, Response $response): Response
    {
        error_log('Session is ' . json_encode($_SESSION));
        $user = $_SESSION['user'];
        if (!isset($user) || !$user['id']) {
            return $response->withStatus(301)->withHeader('Location', '/user/login');
        }
        $body = $this->view->render('Navigation/CreateNewPosts.twig', [
            'user'=> $_SESSION['user']
        ]);
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

        error_log('Session is ' . json_encode($_SESSION));
        $body = $this->view->render('index.twig', [
            'posts' => $posts,
            'showAuthButton' => true,
            'showUserInfo' => false,
            'user'=> $_SESSION['user']
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
        $comments = $this->commentRepository->getAllComments($post['id']);

        error_log('Session is ' . json_encode($_SESSION));
        $body = $this->view->render('post.twig', [
            'post' => $post,
            'comments' => $comments,
            'user'=> $_SESSION['user']
        ]);
        $response->getBody()->write($body);

        return $response;
    }


    /*
     Заморожено до лучших времен(Малая эффективность для реабилитации страницы blog)
    function getallOldPage(Response $response)
    {
        $page = isset($args['page']) ? (int) $args['page'] : 1; // проверка на обьявление переменной и отличие от значение null
        $limit = 2; // Отрисовка страниц на /blog(Лимит отрисовки страниц на одной страницы /blog)

        // извлечение всех записей блога для текущей страницы(blog/1 последние 2 поста, blog/2 последние 3-4 поста и так далее
        $posts = $this->->showall($page, $limit, 'DESC');
        $totalCount = $this->postMapper->getTotalCount(); // получение общего кол-ва постов для расчета выдачи постов для пагинации
        // rendering .twig file(рендеринг .twig файла сохранение выходных данных в $body) and save data in $body
        $body = $this->view->render('blog.twig', [
            'posts' => $posts, // giving list posts for templates in display (выдача списка постов в шаблон для отображение)
            'pagination' => [
                'current' => $page, // current page number(текущ. номер страницы)
                'paging' => ceil($totalCount / $limit) // вычисление всего кол-ва страниц через $totalCount деля на $limit и округления ceilом
            ]
        ]);
        $response->getBody()->write($body);
        return $response;
    }
*/
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
    function createNewPostComment(Request $request, Response $response, array $args): Response
    {

        $user = $_SESSION['user'];
        if (!isset($user) || !$user['id']) {
            return $response->withStatus(301)->withHeader('Location', '/user/login');
        }


        error_log('Initial to create for post');
        $comment = [];
        $comment['content'] = $_POST['content'];
        $comment['post_id'] = $args['post_id'];
        $comment['author_id'] = $user['id'];
        error_log('include comment repository');
        $this->commentRepository->createComment($comment);
        return $response;
    }
}