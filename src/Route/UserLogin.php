<?php


namespace Blog\Route;

session_start();

use Blog\UserInfo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

//use PDO;
//use PDOException;
//use PhpDevCommunity\DotEnv;
//use Psr\Http\Message\ResponseInterface;
//use Psr\Http\Message\ServerRequestInterface;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserLogin
{
    private Environment $twig;
    private UserInfo $userInfo;

    public function __construct(Environment $twig, UserInfo $userInfo) {
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
                $user = $this->userInfo->verifyUser($login, $password);
                if ($user) {
                    $_SESSION['user'] = ['id' => $user['id'], 'login' => $user['login']];
                    $message = 'Авторизация успешна!';
                } else {
                    $message = 'Ошибка: Неверный логин или пароль!';
                }
            } else {
                $message = 'Заполните все поля!';
            }
        }

        $body = $this->twig->render('user-login.twig', ['message' => $message]);
        $response->getBody()->write($body);
        return $response;
    }
// Передача запроса
//        $userInfo['name'] = 'Nikita';
//        $userInfo['score'] = 100;
//        $body = $this->view->render('user-login.twig',
//            [
//                'userInfo' => $userInfo
//            ]);
//
//        $res->getBody()->write($body);
//
//        return $res;
//    }



/* Альтернативная версия
 public function __invoke(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $userInfo = [];
        $userInfo['name'] = 'Nikita';
        $userInfo['score'] = 100;
        $body = $this->view->render('user-login.twig',
            [
                'userInfo' => $userInfo
            ]);

        $res->getBody()->write($body);

        return $res;
    }
}
*/
}
// Данные для шаблона

//        $body = $this->view->render('user-login.twig'); // [] передача аргументов