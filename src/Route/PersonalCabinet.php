<?php

namespace Blog\Route;

use Blog\PostRepository;
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

    private PostRepository $postRepository;
    public function __construct(Environment $view, DataBase $dataBase, PostRepository $postRepository)
    {
        $this->view = $view;
        $this->dataBase = $dataBase;
        $this->postRepository = $postRepository;
    }

    // Отрисовка Личного кабинета
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

        $posts = $this->postRepository->prepareInfoPost( (int) $post_id, $args);

        if (empty($posts)) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $post = $posts[0];

        error_log('Title Value is' . json_encode($post_id));
        error_log('$post is ' . json_encode($post));
        $body = $this->view->render('Navigation/PostEditor.twig', [
            'post' => $post,
            'user'=> $_SESSION['user']
        ]);
        $response->getBody()->write($body);

        return $response;
    }
    public function PostUpdate(Request $request, Response $response, array $args)
    {
        $title = $_POST['title'];
        error_log('Title Value is' . json_encode($title));
        $content = $_POST['content'];
        error_log('Title Value is' . json_encode($content));
//            $post_id = (int)$args['post_id'];
//        $post_id = 3;
//        error_log('ID Value is' . json_encode($post_id));
        $update = $this->postRepository->updatePosts($title, $content); //$post_id
        print_r($update);
    }

}