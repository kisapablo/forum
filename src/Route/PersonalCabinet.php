<?php

namespace Blog\Route;

use Blog\PostRepository;
use Blog\DataBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class PersonalCabinet
{
    private Environment $view;

    private PostRepository $postRepository;
    public function __construct(Environment $view, PostRepository $postRepository)
    {
        $this->view = $view;
        $this->postRepository = $postRepository;
    }

    // Отрисовка Личного кабинета
    public function showPersonalCabinet(Request $request, Response $response): Response
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
    }

//    public function showPostEditor($connect, Response $response, array $args): Response
//    {
//
////        if (!isset($user) || !$user['id']) {
////            return $response->withStatus(301)->withHeader('Location', '/user/login');
////        }
////
//        $body = $this->view->render('Navigation/PostEditor.twig', [
//            'user' => $_SESSION['user'],
////            'posts' => $infoPost
//        ]);
//        $response->getBody()->write($body);
//        return $response;
//        // Конец отрисовки
//    }

    public function getPostInfo(Request $request, Response $response, array $args)
    {
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $post_id = (int)$args['post_id'];

        $post = $this->postRepository->findPostById( (int) $post_id);

        if ($post == null) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        error_log('Title Value is' . json_encode($post_id));
        error_log('$post is ' . json_encode($post));
        $body = $this->view->render('Navigation/PostEditor.twig', [
            'post' => $post,
            'user'=> $_SESSION['user']
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
//            $post_id = (int)$args['post_id'];
//        $post_id = 3;
//        error_log('ID Value is' . json_encode($post_id));
        $update = $this->postRepository->updatePosts($title, $content); //$post_id
//        print_r($update);
        return $response->withStatus(301)->withHeader('Location', '/posts/' . (int)$args['post_id']);
    }

    public function showPublishedPosts(Request $request, Response $response, array $args = []): Response
    {
        $user = $_SESSION['user'];
        $userId = $user['id'];

        error_log('ID Value is' . json_encode($userId));

        $post_id = (int)$args['post_id'];
        error_log('Post_ID Value is' . json_encode($post_id));

        $posts = $this->postRepository->findAllPostsByAuthorId($userId);

        error_log('Posts Value is' . json_encode($posts));

        error_log('Session is ' . json_encode($_SESSION));
//        if (!isset($user) || !    $user['userId']) {
//            return $response->withStatus(301)->withHeader('Location', '/user/login');
//        }
//
        $body = $this->view->render('Navigation/PersonalCabinet-SelectPosts.twig', [
            'posts' => $posts,
            'user' => $_SESSION['user']
        ]);
        $response->getBody()->write($body);
        return $response;
        // Конец отрисовки
    }


}