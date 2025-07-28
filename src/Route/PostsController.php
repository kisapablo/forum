<?php

namespace Blog\Route;


use Blog\CommentRepository;
use Blog\DataBase;
use PDO;
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

    // rendering Create Post Builder(CreateNewPosts.twig)
    public function showPostBuilderPage(Request $request, Response $response): Response
    {
        error_log('Check for authorization');
        error_log('Session is ' . json_encode($_SESSION));
        $user = $_SESSION['user'];
        $body = $this->view->render('Navigation/CreateNewPosts.twig', [
            'user' => $_SESSION['user']
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function getTotalCount(): int
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare( // Если не робит то добавь перед getConnection, dataBase->
            'SELECT count(id) as total FROM post'
        );


        $statement->execute();

        return (int) ($statement->fetchColumn() ?? 0);
    }
// rendering index.twig
    function showAllPosts(Request $request, Response $response, array $args): Response
    {
        // Проверяем переменная обьявлена ли и разницу с null
        $page = isset($args['page']) ? (int) $args['page'] : 1;
        // Лимит отрисовки страниц(если будет 5 постов то отрисуется только 3 из них если лимит равен 3)
        $limit = (int) 3;

        $start = (int) (($page - 1) * $limit);

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * FROM post ORDER BY publication_date DESC LIMIT :limit OFFSET :start     ' //:start
        );

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('start', $start, PDO::PARAM_INT);
        $statement->execute();


        $posts = $statement->fetchAll();

        $totalCount = $this->getTotalCount();
        error_log('Session is ' . json_encode($_SESSION));
        $body = $this->view->render('index.twig', [
            'posts' => $posts,
            'showAuthButton' => true,
            'showUserInfo' => false,
            'user' => $_SESSION['user'],
            'pagination' => [
                'current' => $page,  // current page number(текущ. номер страницы)
                'paging' => ceil($totalCount / $limit) // вычисление всего кол-ва страниц через $totalCount деля на $limit и округления ceilом
            ]
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