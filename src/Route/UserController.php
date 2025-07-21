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

    //post request

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
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
            $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name']];
            return $response->withStatus(301)->withHeader('Location', '/user');
        } catch (Exception $e) {
            $message = 'Ошибка: '. $e->getMessage();
            $body = $this->view->render('user-login.twig', ['message' => $message]); // $e->getMessage();
            $response->getBody()->write($body);
            return $response;
        }
    }
}
