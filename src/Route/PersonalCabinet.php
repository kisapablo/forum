<?php

namespace Blog\Route;

use Blog\UserRepository;
use Blog\PostRepository;
use Blog\DataBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class PersonalCabinet
{
    private Environment $view;

    private PostRepository $postRepository;

    private UserRepository $userRepository;

    public function __construct(Environment $view, PostRepository $postRepository, UserRepository $userRepository)
    {
        $this->view = $view;
        //        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
    }

    // Отрисовка Личного кабинета
    public function showPersonalCabinet(Request $request, Response $response, array $args): Response
    {
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        $cabinet = $this->userRepository->cabinetInfo($_SESSION['user']['id']);
        error_log('cabinet info is' . json_encode($cabinet));
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }
        error_log('Session is ' . json_encode($_SESSION));
        error_log('ico' . json_encode($icon));
        error_log('icon = ' . json_encode($icon['icon_name']));
        //        if (!isset($user) || !$user['id']) {
        //            return $response->withStatus(301)->withHeader('Location', '/user/login');
        //        }

        $body = $this->view->render('Navigation/PersonalCabinet.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon,
            'cabinet' => $cabinet,
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function showAdminPanel(Request $request, Response $response, array $args): Response
    {
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }
        error_log('Session is ' . json_encode($_SESSION));
        //        if (!isset($user) || !$user['id']) {
        //            return $response->withStatus(301)->withHeader('Location', '/user/login');
        //        }

        $cabinet = $this->userRepository->cabinetInfo($_SESSION['user']['id']);

        $body = $this->view->render('Navigation/admin.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon,
            'cabinet' => $cabinet
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function getGlobalVariable()
    {
        echo 'Post Value is ';
        print_r($_POST);
        echo '<br> Files Value is ';
        print_r($_FILES);
        echo '<br> Session Value is ';
        print_r($_SESSION);
        echo '<br> Cookie Value is ';
        print_r($_COOKIE);
    }
}
