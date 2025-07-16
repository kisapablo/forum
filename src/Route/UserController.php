<?php

namespace Blog\Route;

use Blog\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class UserController
{
    /**
     * @var Environment
     */
    private Environment $view;
    private UserRepository $userRepository;

    /**
     * @param Environment $view
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
                $_SESSION['user'] = ['id' => $user['id'], 'login' => $user['login']];
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

    //post request
    public function authorizeUser(Request $request, Response $response): Response
    {

        $login = $_POST['login'];
        $password = $_POST['password'];

        if (!empty($login) && !empty($password)) {
            $user = $this->userRepository->verifyUser($login, $password);
            if ($user) {
                $_SESSION['user'] = ['id' => $user['id'], 'login' => $user['login']];
            } else {
                $message = 'Ошибка: Неверный логин или пароль!';
                $body = $this->view->render('user-login.twig', ['message' => $message]);
                $response->getBody()->write($body);
                return $response;
            }
        } else {
            $message = 'Заполните все поля!';
            $body = $this->view->render('user-login.twig', ['message' => $message]);
            $response->getBody()->write($body);
            return $response;
        }

        return $response->withStatus(301)->withHeader('Location', '/user');
    }
}
