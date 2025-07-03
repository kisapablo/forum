<?php

namespace Blog\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class UserRegistration
{


    private Environment $view;

    public function __construct(Environment $view)
    {
        $this->view = $view;
    }

    public function __invoke(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $userInfo = [];
        $userInfo['name'] = 'Nikita';
        $userInfo['score'] = 100;
        $body = $this->view->render('user-registration.twig',
         [
             'userInfo' => $userInfo
         ]);

        $res->getBody()->write($body);

        return $res;
    }
}