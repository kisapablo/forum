<?php

namespace Blog\Route;

require_once "config/database.php";
require_once 'src/UserRepository.php';
require_once 'vendor/autoload.php';

use Blog\UserRepository;
use Twig\Environment;
//use Twig\Loader\FilesystemLoader;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserRegistration
{
    private Environment $twig;
    private UserRepository $userInfo;

    public function __construct(Environment $twig, UserRepository $userInfo) {
        $this->twig = $twig;
        $this->userInfo = $userInfo;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function __invoke(Request $request, Response $response): Response {
        $message = '';

        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $login = $data['login'] ?? '';
            $password = $data['password'] ?? '';

            if (!empty($login) && !empty($password)) {
                if ($this->userInfo->addUser($login, $password)) {
                    $message = 'Пользователь успешно добавлен!';
                } else {
                    $message = 'Ошибка: Логин уже существует!';
                }
            } else {
                $message = 'Заполните все поля!';
            }
        }

        $body = $this->twig->render('user-registration.twig', ['message' => $message]);
        $response->getBody()->write($body);
        return $response;
    }
}



//    public function __invoke(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
//    {
//        $userInfo = [];
//        $userInfo['name'] = 'Nikita';
//        $userInfo['score'] = 100;
//        $body = $this->view->render('user-registration.twig',
//            [
//                'userInfo' => $userInfo
//            ]);
//
//        $res->getBody()->write($body);
//
//        return $res;
//    }
//}
//    function __invoke(): void
//    {
//        $loader = new FilesystemLoader('/templates');
//        $twig = new Environment($loader);
//
//        $message = '';
//
//        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//            $login = $_POST['login'] ?? '';
//            $password = $_POST['password'] ?? '';
//
//            if (!empty($login) && !empty($password)) {
//                $userInfo = new UserInfo();
//                $userInfo->addUser($login, $password);
//                echo "Пользователь успешно добавлен!";
//            } else {
//                echo "Заполните все поля!";
//            }
//        }
//        $res->getBody()->write($body);
//
//        return $res;

