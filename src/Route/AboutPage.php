<?php

namespace Blog\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Twig\Environment;

class AboutPage
{
    /**
     * @var Environment
     */
    private Environment $view;

    /**
     * @param Environment $view
     */
    public function __construct(Environment $view)
    {
        $this->view = $view;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    // Отрисовка /page
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->view->render('about.twig', [
            'name' => 'Nikita',
            'animals' => 'Dogs'
        ]);
        $response->getBody()->write($body);
        return $response;
    // Конец отрисовки
    }
}