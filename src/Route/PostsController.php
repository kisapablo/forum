<?php

namespace Blog\Route;


use Blog\CommentRepository;
use Blog\DataBase;
use Blog\PostRepository;
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

    private CommentRepository $commentRepository;
    private PostRepository $postRepository;

    public function __construct(Environment       $view,
                                CommentRepository $commentRepository,
                                PostRepository    $postRepository)
    {
        $this->view = $view;
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
    }

    // rendering Create Post Builder(CreateNewPosts.twig)
    public function showPostBuilderPage(Request $request, Response $response): Response
    {
        error_log('Check for authorization');
        error_log('Session is ' . json_encode($_SESSION));

        $user = $_SESSION['user'];
        $body = $this->view->render('Navigation/CreateNewPosts.twig', [
            'user' => $user,
        ]);

        $response->getBody()->write($body);

        return $response;
    }


// rendering index.twig
    function showAllPosts(Request $request, Response $response, array $args = []): Response
    {
        // Проверяем переменная обьявлена ли и разницу с null
        $page = isset($args['page']) ? (int)$args['page'] : 1;
        // Лимит отрисовки страниц(если будет 5 постов то отрисуется только 3 из них если лимит равен 3)
        $limit = 3;
        $start = (int)(($page - 1) * $limit);

        $posts = $this->postRepository->findAllPosts($args, $page, $limit, $start);

        $totalCount = $this->postRepository->getTotalCount();
        error_log('Session is ' . json_encode($_SESSION));
        error_log('Session id is ' . json_encode($_SESSION['id']));
        $body = $this->view->render('index.twig', [
            'posts' => $posts,
            'showAuthButton' => true,
            'showUserInfo' => false,
            'user' => $_SESSION['user'],
            'id' => $_SESSION['id'],
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

        $post = $this->postRepository->findPostById($post_id);

        if ($post == null) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $comments = $this->commentRepository->getAllComments($post['id']);

        error_log('Session is ' . json_encode($_SESSION));
        $body = $this->view->render('post.twig', [
            'post' => $post,
            'comments' => $comments,
            'user' => $_SESSION['user']
        ]);
        $response->getBody()->write($body);

        return $response;
    }

    public function createNewPost(Request $request, Response $response): Response
    {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $id = $_SESSION['id'];


        $this->postRepository->addNewPost($title, $content, $id);

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
        $comment = [
            'content' => $_POST['content'],
            'post_id' => $args['post_id'],
            'author_id' => $user['id']
        ];

        error_log('include comment repository');
        $this->commentRepository->createComment($comment);

        return $response->withStatus(301)->withHeader('Location', '/posts/'.$args['post_id']);
    }
}