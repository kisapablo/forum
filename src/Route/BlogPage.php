<?php

namespace Blog\Route;

use Blog\PostMapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class BlogPage
{
    /**
     * @var Environment
     */
    private Environment $view;

    /**
     * @var PostMapper
     */
    private PostMapper $postMapper;
    /**
     * BlogPage constructor?.
     * @param Environment $view
     * @param PostMapper $postMapper
     */
    public function __construct(Environment $view, PostMapper $postMapper)
    {
        $this->view = $view;
        $this->postMapper = $postMapper;
    }
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = isset($args['page']) ? (int) $args['page'] : 1; // проверка на обьявление переменной и отличие от значение null
        $limit = 2; // Отрисовка страниц на /blog(Лимит отрисовки страниц на одной страницы /blog)

        // извлечение всех записей блога для текущей страницы(blog/1 последние 2 поста, blog/2 последние 3-4 поста и так далее
        $posts = $this->postMapper->getList($page, $limit, 'DESC');
        $totalCount = $this->postMapper->getTotalCount(); // получение общего кол-ва постов для расчета выдачи постов для пагинации
       // rendering .twig file(рендеринг .twig файла сохранение выходных данных в $body) and save data in $body
        $body = $this->view->render('blog.twig', [
            'posts' => $posts, // giving list posts for templates in display (выдача списка постов в шаблон для отображение)
            'pagination' => [
                'current' => $page, // current page number(текущ. номер страницы)
                'paging' => ceil($totalCount / $limit) // вычисление всего кол-ва страниц через $totalCount деля на $limit и округления ceilом
            ]
        ]);
        $response->getBody()->write($body);
        return $response;
    }
}
