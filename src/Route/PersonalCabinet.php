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
        $user = $_SESSION['user'];

        $icon = $this->userRepository->findUserIcon($user['id']);
        error_log('Session is ' . json_encode($_SESSION));
        error_log('user' . json_encode($user));
        error_log('ico' . json_encode($icon));
        error_log('icon = ' . json_encode($icon['icon_name']));
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





    // Selecting Posts... Personal Cabinet SelectPosts
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

    public function showUserEditor(Request $request, Response $response)
    {
        $user = $_SESSION['user'];

        $icon = $this->userRepository->findUserIcon($user['id']);
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
        $user = $_SESSION['user'];
        $userId = $user['id'];

        $fileDir = "/public/images/" ;
        $fileName = $fileDir . $iconName;
        if (isset($_FILES) && $_FILES['avatar']['error'] == 0)
        {
            $dir = "./public/images/" . $_FILES['avatar']['name'];
            error_log('File name '. $dir);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $dir);

        }
        else
        {
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
        $icon = $this->userRepository->getdefaultIcon();
        $body = $this->view->render('Navigation/SelectAvatar.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon
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
        $user = $_SESSION['user'];
        $userId = $user['id'];
        $this->userRepository->saveDefaultIcon($defaultIcon, $userId);
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
    /*
    public function updateUserInfo(Request $request, Response $response)
    {
        $UserName = $_POST['Username'];
        $User_Content = $_POST['user_content'];
        $Newavatar = $_POST['avatar'];
        error_log('$_POST Value is' . json_encode($_POST));
        error_log('$User_Content Value is' . json_encode($User_Content));
        error_log('$Username Value is' . json_encode($UserName));
        error_log('$NewAvatar Value is' . json_encode($Newavatar));
    }
    */
}