<?php

namespace Blog\Route;

use Blog\UserRepository;
use Blog\CommentRepository;
use Blog\DataBase;
use Blog\PostRepository;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;


class PostsController
{

    /**
     * @var Environment
     */
    private Environment $view;

    private CommentRepository $commentRepository;
    private PostRepository $postRepository;
    private UserRepository $userRepository;

    public function __construct(
        Environment       $view,
        CommentRepository $commentRepository,
        PostRepository    $postRepository,
        UserRepository    $userRepository
    ) {
        $this->view = $view;
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
    }

    // rendering Create Post Builder(CreateNewPosts.twig) PostBuilder Post Builder rendering
    public function showPostBuilderPage(Request $request, Response $response): Response
    {
        error_log('Check for authorization');
        error_log('Session is ' . json_encode($_SESSION));

        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }

        $body = $this->view->render('Navigation/CreateNewPosts.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon,
        ]);

        $response->getBody()->write($body);

        return $response;
    }

    public function showLeaderKarma(Request $request, Response $response)
    {
        error_log('Session is ' . json_encode($_SESSION));

        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }

        $KarmaSession = $this->postRepository->getKarmaSession($_SESSION['user']['id']);
        error_log("User Karma is ". json_encode($KarmaSession));
        // error_log("User Karma is ". json_encode($KarmaSession['karma']));
        $LeadersKarma = $this->postRepository->getAllKarma();
        error_log("Leaders Karma is " . json_encode($LeadersKarma));
        error_log("Karma value is " . json_encode($LeadersKarma['karma']));

        $body = $this->view->render('Navigation/LeaderKarma.twig', [
        'user' => $_SESSION['user'],
        'icons' => $icon,
        'rateleaders' => $LeadersKarma,
        'userrate' => $KarmaSession,
        ]);

        $response->getBody()->write($body);

        return $response;
    }

    // rendering index.twig
    function showAllPosts(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        // Проверяем переменная обьявлена ли и разницу с null
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        // Лимит отрисовки страниц(если будет 5 постов то отрисуется только 3 из них если лимит равен 3)
        $limit = 3;
        $start = (int)(($page - 1) * $limit);
        error_log("Start =" . json_encode($start));

        // Ищем посты которые опубликовал пользователь
        if (isset($params['author'])) {
            $authorId = (int)$params['author'];
            $posts = $this->postRepository->findAllPostsByAuthorId($authorId, $limit, $start);
            $baseUrl = 'posts?author=' . $authorId . '&';
        } else {
// Рендер постов на главной странице
            $posts = $this->postRepository->findAllPosts($limit, $start);
            $baseUrl = 'posts?';
        }

        // Ищем посты в которых есть имя которое отправил пользователь
        if (isset($params['search'])) {
            $searchName = $params['search'];
            $outposts = [];
            foreach ($posts as $post) {
                if (str_contains($post['title'], $searchName)) {
                    $outposts[] = $post;
                }
            }
            $posts = $outposts;
        }

        // Форматируем посты для поиска тэгов
        $ids = array_map(fn($p): int => $p['id'], $posts);
        // Получаем тэги из бдшки
        $tags = $this->postRepository->getAllPostTag($ids);
        error_log('TagsV' . json_encode($tags));
        // Если пользователь хочет найти пост то возвращаем ему инной массив
        if (isset($params['tags'])) {
            $Querytags = (int)$params['tags'];
            error_log("Query Value" . json_encode($Querytags));
            $posts = $this->postRepository->getAllPostWhereTag($posts);
            error_log("Ids Value" . json_encode($ids));
            error_log("BaseUrl Value" . json_encode($baseUrl));
            $baseUrl = 'posts?tags=' . $Querytags . '&';
        }

        // Добавляем теги к массиву постов
        for ($i = 0; $i < count($posts); $i++) {
            $postTags = [];
            $post = $posts[$i];

            foreach ($tags as $tag) {
                if ($tag['post_id'] == $post['id']) {
                    $postTags[] = $tag['tag_name'];
                }
            }

            $posts[$i]['tags'] = $postTags;
        }


        error_log("Ids Value" . json_encode($ids));
        if($_SESSION['user'] != null)
{
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
}
        //        $userIcon = $this->userRepository->findUserIcon($posts['author_id']);
        $totalCount = $this->postRepository->getTotalCount();
        error_log('Session is ' . json_encode($_SESSION));
        error_log('Post Value ' . json_encode($posts));
        $body = $this->view->render('index.twig', [
            'posts' => $posts,
            'showAuthButton' => true,
            'showUserInfo' => false,
            'user' => $_SESSION['user'],
            'icons' => $icon,
            'baseUrl' => $baseUrl,
            'pagination' => [
                'current' => $page,  // current page number(текущ. номер страницы)
                'pagesCount' => ceil($totalCount / $limit), // вычисление всего кол-ва страниц через $totalCount деля на $limit и округления ceilом
            ]
        ]);

        $response->getBody()->write($body);
        return $response;
    }

    // rendering DeletePosts.twig
    function showDeletePosts(Request $request, Response $response, array $args): Response
    {
    error_log('Args value' . json_encode($args));
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }
        $post_id = (int)$args['post_id'];
        error_log('pid var' . json_encode($post_id));
        $post = $this->postRepository->findPostById($post_id);
        if (!isset($post)) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        $totalCount = $this->postRepository->getTotalCount();
        error_log('Session is ' . json_encode($_SESSION));
        $body = $this->view->render('Navigation/DeletePost.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon,
            'post' => $post
        ]);

        $response->getBody()->write($body);
        return $response;
    }

    public function DeletePost(Request $request, Response $response, array $args)
    {
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }
        $post_id = (int)$args['post_id'];

        // Удаляем комментарии с поста
        $statuscomment = $this->postRepository->deleteAllPostComment($post_id);
        // Удаляем пост
        $statuspost = $this->postRepository->deletePost($post_id);
        error_log('Status Delete Comment is ' . json_encode($statuscomment));
        error_log('Status Delete Comment is ' . json_encode($statuspost));
        //    if (!empty($statuspost)){
        //        $message = 'Удаление запроса произошло успешно';
        //    } else{
        //        $message = 'Что-то пошло не так во время удаления поста';
        //    }

        // Возвращаем пользоателя на страницу с постами
        return $response->withStatus(301)->withHeader('Location', '/');
    }

    // rendering DeleteComments.twig
    function showDeleteComments(Request $request, Response $response, array $args): Response
    {
        // if (!isset($args['post_id'])) {
        //     $body = $this->view->render('not-found.twig');
        //     $response->getBody()->write($body);
        //     return $response;
        // }
        // if (!isset($args['comment_id'])) {
        //     $body = $this->view->render('not-found.twig');
        //     $response->getBody()->write($body);
        //     return $response;
        // }
        //        $totalCount = $this->postRepository->getTotalCount();
                $post_id = (int)$args['post_id'];
        error_log('pid var' . json_encode($post_id));
        $post = $this->postRepository->findPostById($post_id);
        $comment_id = (int)$args['comment_id'];
        $comments = $this->commentRepository->findWhereComments($comment_id);
        if (!isset($post) || !isset($comments)) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }
error_log('Comments finded' . json_encode($comments));
error_log('Comments finded' . json_encode($post));
        error_log('Delete Comments Value is ' . json_encode($comments));
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        error_log('Session is ' . json_encode($_SESSION));
        $body = $this->view->render('Navigation/DeleteComments.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon,
        ]);

        $response->getBody()->write($body);
        return $response;
    }

    public function DeleteComment(Request $request, Response $response, array $args)
    {
        $comment_id = (int)$args['comment_id'];
        $post_id = (int)$args['post_id'];

        // Удаляем комментарии с поста
        $deletecomment = $this->postRepository->deleteComment($comment_id);
        error_log('Status Delete Comment is ' . json_encode($deletecomment));

        // Возвращаем пользоателя на страницу с постами
        return $response->withStatus(301)->withHeader('Location', '/posts/' . $post_id);
    }

    // Rendering Post.twig
    public function showPostPage(Request $request, Response $response, array $args = [])
    {
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $CommentAvatar = $this->userRepository->findUserIcon($_SESSION['user']['id']);

        $post_id = (int)$args['post_id'];
        error_log('Post ID is ' . json_encode($post_id));
        $post = $this->postRepository->findPostById($post_id);
        error_log('post value ' . json_encode($post));
        $postAttachment = $this->postRepository->getPostAttachmentView($post_id);
        $icons = $this->userRepository->findUserIcon($post['author_id']);
        error_log('icons value is' . json_encode($icons));
        //        $userIcon = $this->userRepository->findAuthorIcon($post['author_id']);
        $userIcon = $this->userRepository->findUserIcon($post['author_id']);
        error_log('Picons value is' . json_encode($userIcon));

        if ($post == null) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $comments = $this->commentRepository->findUserComments($post['id']);

        $ids = array_map(fn($c): int => $c['id'], $comments);

        $attachments = $this->commentRepository->findCommentAttachmentsByIds($ids);

        foreach ($attachments as $a) {
            for ($i = 0; $i < count($comments); $i++) {
                if ($comments[$i]['id'] == $a['comment_id']) {
                    $comments[$i]['attachments'][] = $a;
                    break;
                }
            }
        }


        error_log('New Comments ' . json_encode($comments));
        error_log('Session is ' . json_encode($_SESSION));
        error_log('Attachment is ' . json_encode($postAttachment));
        error_log('Post is ' . json_encode($post));
        $body = $this->view->render('post.twig', [
            'post' => $post,
            'comments' => $comments,
            'user' => $_SESSION['user'],
            'post_attachments' => $postAttachment,
            'icon' => $userIcon,
            'icons' => $icons
        ]);

        $response->getBody()->write($body);

        return $response;
    }

    public function createNewPost(Request $request, Response $response): Response
    {
        // Извлекаем необходимые данные для создания поста
        $title = $_POST['title'];
        $content = $_POST['content'];
        $id = $_SESSION['user']['id'];
        // Показываем Пользовательскую иконку
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }
        // Загружаем прикрепленные файлы если таковые имеются
        $iconName = $_FILES['avatar']['name'];
        $userId = $_SESSION['user']['id'];

        $fileDir = "/public/images/";
        $fileName = $fileDir . $iconName;
        if (isset($_FILES) && $_FILES['avatar']['error'] == 0) {
            $dir = "./public/images/" . $_FILES['avatar']['name'];
            error_log('File name ' . $dir);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $dir);
        }
        error_log('FileName Value is ' . json_encode($fileName));
        error_log('FILES is ' . json_encode($_FILES));

        // Создаем Пост и создаем прикрепленные файлы для поста
        $post = $this->postRepository->addNewPost($title, $content, $id);
        $PAttachment = $this->postRepository->savePostAttachment($post, $fileName);
        error_log('PAttachment value is ' . json_encode($PAttachment));

        // Отрисовываем шаблон для создания постов
        $body = $this->view->render('Navigation/CreateNewPosts.twig', [
            'icons' => $icon
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    function createNewPostComment(Request $request, Response $response, array $args): Response
    {
        // Проверяем на авторизован пользователь или нет
        if (!isset($_SESSION['user']) || !$_SESSION['user']['id']) {
            return $response->withStatus(301)->withHeader('Location', '/user/login');
        }

        // Создаем массив в который ложим необходимые аргументы для создания комментария
        $comment = [
            'content' => $_POST['content'],
            'post_id' => $args['post_id'],
            'author_id' => $_SESSION['user']['id']
        ];


        // Загружаем прикрепленные файлы к комментарию если таковые имеются
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
        // Создание комментария
        $comments = $this->commentRepository->createComment($comment, $fileName, $userId);
        error_log('Comments Value is ' . json_encode($comments));
        error_log('Userid[author_id] value is ' . json_encode($userId));
        // Делаем прикрепление к комментарию
        $CAttachment = $this->commentRepository->saveCommentAttachment($comments, $fileName);
        error_log("CAttachment" . json_encode($CAttachment));
        // Возвращаем пользователя к посту к которому он сделал комментарий
        return $response->withStatus(301)->withHeader('Location', '/posts/' . $args['post_id']);
    }

    // rendering Post Editor
    public function getPostInfo(Request $request, Response $response, array $args)
    {
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $post_id = (int)$args['post_id'];

        $post = $this->postRepository->findPostById((int)$post_id);

        if ($post == null) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }
        // Показываем пользовательскую иконку и отрисовываем Редактор постов
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }

        error_log('Title Value is' . json_encode($post_id));
        error_log('$post is ' . json_encode($post));
        $body = $this->view->render('Navigation/PostEditor.twig', [
            'post' => $post,
            'user' => $_SESSION['user'],
            'icons' => $icon
        ]);

        $response->getBody()->write($body);

        return $response;
    }

    public function updatePost(Request $request, Response $response, array $args)
    {
        $title = $_POST['title'];
        error_log('Title Value is' . json_encode($title));
        $content = $_POST['content'];
        error_log('Content Value is' . json_encode($content));
        $post_id = (int)$args['post_id'];
        error_log('ID Value is' . json_encode($post_id));
        $update = $this->postRepository->updatePosts($title, $content, $post_id);
        $iconName = $_FILES['attachment']['name'];

        // Обновляем загруженные файлы и удаляем(Но на самом деле создаем еще один файлик)
        $fileDir = "/public/images/";
        $fileName = $fileDir . $iconName;
        if (isset($_FILES) && $_FILES['avatar']['error'] == 0) {
            $dir = "./public/images/" . $_FILES['avatar']['name'];
            error_log('File name ' . $dir);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $dir);
        } else {
            exit("error!");
        }
        error_log('FileName Value is ' . json_encode($fileName));
        error_log('FILES is ' . json_encode($_FILES));
        // Обновляем стобец в БД(но на самом деле добавляем)
        $PAttachment = $this->postRepository->savePostAttachment($post_id, $fileName);
        error_log('PAttachment value is ' . json_encode($PAttachment));
        return $response->withStatus(301)->withHeader('Location', '/posts/' . (int)$args['post_id']);
    }
    // rendering Comment Editor
    public function getCommentInfo(Request $request, Response $response, array $args)
    {
        if (!isset($args['post_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }
        if (!isset($args['comment_id'])) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }

        $post_id = (int)$args['post_id'];
        $comment_id = (int)$args['comment_id'];

        $comment = $this->postRepository->findCommentById($comment_id);

        error_log('comment id value is ' . json_encode($comment_id));
        if ($comment_id == null) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }
        if ($post_id == null) {
            $body = $this->view->render('not-found.twig');
            $response->getBody()->write($body);
            return $response;
        }
        // Показываем пользовательскую иконку и отрисовываем Редактор постов
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }

        error_log('Title Comment Value is' . json_encode($comment_id));
        $body = $this->view->render('Navigation/CommentEditor.twig', [
            'comment' => $comment,
            'user' => $_SESSION['user'],
            'icons' => $icon
        ]);

        $response->getBody()->write($body);

        return $response;
    }
    public function NotFoundURL(Request $request, Response $response, array $args) {

        $url_id = (int)$args['url_id'];

        // Показываем пользовательскую иконку и отрисовываем шаблон не найденного обработчика
        $icon = $this->userRepository->findUserIcon($_SESSION['user']['id']);
        if ($icon == null) {
            error_log("User#" . $_SESSION['user']['id'] . " has no icon");
        }
 $body = $this->view->render('not-found.twig', [
            'user' => $_SESSION['user'],
            'icons' => $icon,
        ]);

        $response->getBody()->write($body);

        return $response;

}

    public function updateComment(Request $request, Response $response, array $args)
    {
        $content = $_POST['content'];
        error_log('Content Value is' . json_encode($content));
        $comment_id = (int)$args['comment_id'];
        error_log('ID Value is' . json_encode($comment_id));
        $update = $this->postRepository->updateComments($content, $comment_id);
        $iconName = $_FILES['attachment']['name'];

        // Обновляем загруженные файлы и удаляем(Но на самом деле создаем еще один файлик)
        $fileDir = "/public/images/";
        $fileName = $fileDir . $iconName;
        if (isset($_FILES) && $_FILES['avatar']['error'] == 0) {
            $dir = "./public/images/" . $_FILES['avatar']['name'];
            error_log('File name ' . $dir);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $dir);
        }
        error_log('FileName Value is ' . json_encode($fileName));
        error_log('FILES is ' . json_encode($_FILES));
        // Обновляем стобец в БД(но на самом деле добавляем)
        //        $PAttachment = $this->postRepository->savePostAttachment($comment_id, $fileName);
        //        error_log('PAttachment value is ' . json_encode($PAttachment));
        return $response->withStatus(301)->withHeader('Location', '/posts/' . (int)$args['post_id']);
    }
}
