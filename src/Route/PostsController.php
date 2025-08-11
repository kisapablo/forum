<?php

namespace Blog\Route;

use Blog\UserRepository;
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
    private UserRepository $userRepository;

    public function __construct(Environment       $view,
                                CommentRepository $commentRepository,
                                PostRepository    $postRepository, UserRepository $userRepository)
    {
        $this->view = $view;
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
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
        error_log('Session id i ' . json_encode($posts['author_name']));
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


    // Rendering Post.twig
    public function showPostPage(Request $request, Response $response, array $args = [])
    {
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $user = $_SESSION['user'];

        $icon = $this->userRepository->findUserIcon($user['id']);

        $post_id = (int)$args['post_id'];

        $post = $this->postRepository->findPostById($post_id);
        $postAttachment = $this->postRepository->getPostAttachmentView($post_id);
        if ($post == null) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $comments = $this->commentRepository->getAllComments($post['id']);
        for ($i = 0; $i < count($comments); $i++) {
            $comment = $comments[$i];
            $comments[$i]['attachments'] = $this->commentRepository->getCommentAttachmentView($comment['id']);
        }

        error_log( 'New Comments ' . json_encode($comments));
//        $commentsAttachment = $this->commentRepository->getCommentAttachmentView($comments);
        error_log('Session is ' . json_encode($_SESSION));
        error_log('Attachment is ' . json_encode($postAttachment));
        error_log('CAttachments is ' . json_encode($comments['attachments']));
//        error_log('CAttachment name is ' . json_encode($commentsAttachment));
        $body = $this->view->render('post.twig', [
            'post' => $post,
            'comments' => $comments,
            'user' => $_SESSION['user'],
            'post_attachments' => $postAttachment,
            'icons' => $icon
        ]);
        $response->getBody()->write($body);

        return $response;
    }

    // PostBuilder Post Builder
    public function createNewPost(Request $request, Response $response): Response
    {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $id = $_SESSION['user']['id'];


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

        return $response->withStatus(301)->withHeader('Location', '/posts/' . $args['post_id']);
    }

    // rendering Post Editor
    public function getPostInfo(Request $request, Response $response, array $args)
    {
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $post_id = (int)$args['post_id'];

        $post = $this->postRepository->findPostById((int)$post_id);

        if ($post == null) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        error_log('Title Value is' . json_encode($post_id));
        error_log('$post is ' . json_encode($post));
        $body = $this->view->render('Navigation/PostEditor.twig', [
            'post' => $post,
            'user' => $_SESSION['user']
        ]);

        $response->getBody()->write($body);

        return $response;
    }

    public function updatePost(Request $request, Response $response, array $args)
    {
        $title = $_POST['title'];
        error_log('Title Value is' . json_encode($title));
        $content = $_POST['content'];
        error_log('Content Value is' . json_encode($content));
        $post_id = (int)$args['post_id'];
        error_log('ID Value is' . json_encode($post_id));
        $update = $this->postRepository->updatePosts($title, $content, $post_id);
        return $response->withStatus(301)->withHeader('Location', '/posts/' . (int)$args['post_id']);
    }
}