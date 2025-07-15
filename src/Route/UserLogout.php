<?php
//
//
//namespace Blog\Route;
//
//session_start();
//
//use Psr\Http\Message\ResponseInterface;
//use Psr\Http\Message\ServerRequestInterface;
//
//class UserLogout
//{
//    public function __invoke(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
//    {
//        if (session_status() === PHP_SESSION_NONE) {
//            session_start();
//        }
//        session_unset($_SESSION['user']);
////         session_destroy();
//        return $res->withHeader('Location', '/user/login')->withStatus(302);
//    }
////}