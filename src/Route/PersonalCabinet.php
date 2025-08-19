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
        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
    }

    // Отрисовка Личного кабинета
    public function showPersonalCabinet(Request $request, Response $response, array $args): Response
    {
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }
        error_log('Session is ' . json_encode($_SESSION));
//        if (!isset($user) || !$user['id']) {
//            return $response->withStatus(301)->withHeader('Location', '/user/login');
//        }

        $body = $this->view->render('Navigation/PersonalCabinet.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon
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

        $body = $this->view->render('Navigation/admin.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    // Selecting Posts... Personal Cabinet SelectPosts
    public function showPublishedPosts(Request $request, Response $response, array $args = []): Response
    {
        $userId = $_SESSION['user']['id'];
        $page = isset($args['page']) ? (int)$args['page'] : 1;
        $totalCount = $this->postRepository->getTotalCountUsers($userId);
        $limit = 3;
        $start = (int)(($page - 1) * $limit);


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
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }

        $body = $this->view->render('Navigation/PersonalCabinet-SelectPosts.twig', [
            'posts' => $posts,
            'user' => $_SESSION['user'],
            'icons' => $icon,
             'pagination' => [
        'current' => $page,  // current page number(текущ. номер страницы)
        'paging' => ceil($totalCount / $limit) // вычисление всего кол-ва страниц через $totalCount деля на $limit и округления ceilом
    ]
        ]);
        $response->getBody()->write($body);
        return $response;
        // Конец отрисовки
    }

    public function showUserEditor(Request $request, Response $response)
    {
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
//        $password = 'helpme';
//$hash = password_hash($password, PASSWORD_DEFAULT); //метод под вопросом
        $body = $this->view->render('Navigation/UserEditor.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon,
//            'generatedhash' => $hash
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function addUserIco(Request $request, Response $response)
    {
        $iconName = $_FILES['avatar']['name'];
        $userId = $_SESSION['user']['id'];

        $fileDir = "/public/images/";
        $fileName = $fileDir . $iconName;
        if (isset($_FILES) && $_FILES['avatar']['error'] == 0) {
            $dir = "./public/images/" . $_FILES['avatar']['name'];
            error_log('File name ' . $dir);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $dir);

        } else {
            exit("error!");
        }
        error_log('filename is ' . json_encode($fileName));
        error_log('Files is ' . json_encode($_FILES));
//        print_r($_FILES);
        $icon = $this->userRepository->saveUserIcon($fileName, $userId);
        print_r($icon);
//                return $response;
        return $response->withStatus(301)->withHeader('Location', '/user');
    }

    public function showDefaultIconsSelect(Request $request, Response $response)
    {
        $icons = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icons == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }

        $icon = $this->userRepository->getdefaultIcon();
        $body = $this->view->render('Navigation/SelectAvatar.twig', [
            'user' => $_SESSION['user'],
            'dicons' => $icon,
            'icons' => $icons
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function getSelectedDefaultIco(Request $request, Response $response)
    {
        echo 'Post Value is ';
        print_r($_POST);
        echo '<br> Session Value is ';
        print_r($_SESSION);
        $defaultIcon = $_POST['selected_icon'];
        $userId = $_SESSION['user']['id'];
        $this->userRepository->setUserIcon($defaultIcon, $userId);
        return $response->withStatus(301)->withHeader('Location', '/user');
    }

    public function getglobalvariable()
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