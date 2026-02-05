<?php

namespace Blog\Route;

use Blog\UserRepository;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserController
{
    /**
     * @var Environment
     */
    private Environment $view;
    private UserRepository $userRepository;

    /**
     * @param Environment $view
     * @param UserRepository $userRepository
     */
    public function __construct(Environment $view, UserRepository $userRepository)
    {
        $this->view = $view;
        $this->userRepository = $userRepository;
    }


    public function showUserLoginPage(Request $request, Response $response): Response
    {
        $body = $this->view->render('user-login.twig', ['message' => '']);
        $response->getBody()->write($body);
        return $response;
    }

    public function showUserRegistrationPage(Request $request, Response $response): Response
    {
        $body = $this->view->render('user-registration.twig', ['message' => '']);
        $response->getBody()->write($body);
        return $response;
    }



    public function registerUser(Request $request, Response $response): Response
    {
        $login = $_POST['login'];
        $password = $_POST['password'];

        if (!empty($login) && !empty($password)) {
            $user = $this->userRepository->addUser($login, $password);
            if ($user) {
                $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name']];
                return $response->withStatus(301)->withHeader('Location', '/user');
            } else {
                $message = 'Ошибка: Неверный логин или пароль!';
            }
        } else {
            $message = 'Заполните все поля!';
        }

        $body = $this->view->render('user-registration.twig', ['message' => $message]);
        $response->getBody()->write($body);
        return $response;
    }

    public function authorizeUser(Request $request, Response $response): Response
    {
        $login = $_POST['login'];
        $password = $_POST['password'];

        if (empty($login) || empty($password)) {
            $message = 'Заполните все поля!';
            $body = $this->view->render('user-login.twig', ['message' => $message]);
            $response->getBody()->write($body);
            return $response;
        }

        try {
            $user = $this->userRepository->findUserByLoginAndPassword($login, $password);
            error_log('Founding result of user ' . json_encode($user));
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name']];
                return $response->withStatus(301)->withHeader('Location', '/user');
                error_log('Password valid');
            } else {
                error_log('Invalid user password');
                throw new Exception('User password is not valid');
            }
        } catch (Exception $e) {
            error_log('Пароль невалиден');
            $message = 'Ошибка: ' . $e->getMessage();
            $body = $this->view->render('user-login.twig', ['message' => $message]); // $e->getMessage();
            $response->getBody()->write($body);
            return $response;
        }
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

    public function UpdateUserInfo(Request $request, Response $response)
    {
        $iconName = $_FILES['avatar']['name'];
        $userId = $_SESSION['user']['id'];

        $fileDir = "/public/images/";
        $fileName = $fileDir . $iconName;
        if (isset($_FILES) && $_FILES['avatar']['error'] == 0) {
            $dir = "./public/images/" . $_FILES['avatar']['name'];
            error_log('File name ' . $dir);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $dir);
        }
        error_log('filename is ' . json_encode($fileName));
        error_log('Files is ' . json_encode($_FILES));

        error_log('Moto value is ' . json_encode($_POST['moto']));
        $desc = $this->userRepository->updateUserInfo($_POST['moto'], $userId);
        error_log('New Moto Value is ' . json_encode($desc));

        $newNickName = $this->userRepository->updateUserName($_SESSION['user']['id'], $_POST['Username']);
        error_log('New Password Hash Value is ' . json_encode($newNickName));
        $generateNewPasswordHash = password_hash($_POST['Userpass'], PASSWORD_DEFAULT);
        $newPasswordHash = $this->userRepository->updatePasswordHash($generateNewPasswordHash, $_SESSION['user']['id']);
        error_log('New Password Hash Value is ' . json_encode($newPasswordHash));
        unset($_SESSION['user']['name']);
        $nick = $this->userRepository->getNewNick($_SESSION['user']['id']);
        error_log('new nick' . json_encode($nick));
        // $_SESSION['user'] = ['name' => $nick['name']];
        $_SESSION['user']['name'] = $nick['name'];
        return $response->withStatus(301)->withHeader('Location', '/');
    }


    public function showSubmitBug(Request $request, Response $response): Response
    {

        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        $body = $this->view->render('report.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function SubmitBug(Request $request, Response $response): Response
    {
        error_log('$_POST VALUE is ' . json_encode($_POST));
        error_log('SESSION VALUE ' . json_encode($_SESSION));
        $sending = $this->userRepository->sendreport($_POST);
        return $response->withStatus(301)->withHeader('Location', '/');
    }

    public function DeleteSession(Request $request, Response $response): Response
    {
        unset($_SESSION['user']);
        return $response->withStatus(301)->withHeader('Location', '/user/login');

    }
}
