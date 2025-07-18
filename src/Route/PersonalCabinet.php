<?php

namespace Blog\Route;

use Blog\config\DBTest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Twig\Environment;

class PersonalCabinet
{

    /**
     * @var Environment
     */
    private Environment $view;

    public function __construct(Environment $view)
    {
        $this->view = $view;
    }

    // Отрисовка Создания постов
    public function __invoke($connect, ResponseInterface $response): ResponseInterface
    {
        error_log('Session is ' . json_encode($_SESSION));

        $body = $this->view->render('Navigation/PersonalCabinet.twig');
        $response->getBody()->write($body);
        return $response;
        // Конец отрисовки
    }
}