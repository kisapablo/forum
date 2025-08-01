<?php

namespace Blog;

use Exception;
use PDO;

class PostRepository
{
    private DataBase $dataBase;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    public function getTotalCount(): int
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare( // Если не робит то добавь перед getConnection, dataBase-> неактуально делай через $connection
            'SELECT count(id) as total FROM post'
        );


        $statement->execute();

        return (int) ($statement->fetchColumn() ?? 0);
    }

public function addNewPost ($title, $content, $id)
{
    $connection = $this->dataBase->getConnection();


    // Вбивание данных из шаблонов
    $statement = $connection->prepare(
        "INSERT INTO post (title, content, author_id, publication_date) 
                VALUES ('$title', '$content', '$id', CURRENT_DATE)"
    );


    $statement->execute();

    return $statement->fetchAll();
}

public function findAllPosts(array $args, $page, $limit, $start)
{
    // Проверяем переменная обьявлена ли и разницу с null

    $connection = $this->dataBase->getConnection();

    $statement = $connection->prepare(
        'SELECT * FROM post ORDER BY publication_date DESC LIMIT :limit OFFSET :start     ' //:start
    );

    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->bindValue('start', $start, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

public function prepareInfoPost($post_id, array $args)
{

    $connection = $this->dataBase->getConnection();

    $statement = $connection->prepare(
        'SELECT * FROM post where id = :id'
    );

    $statement->execute([
        'id' => $post_id
    ]);


    return $statement->fetchAll();
}

public function updatePosts($title, $content,) // ,$post_id
{
    $connection = $this->dataBase->getConnection();


    // Изменение данных из шаблонов
    $statement = $connection->prepare(
        "UPDATE post SET
                content = '$content',
                title = '$title'
                WHERE id = 3"
    //                content = :content,
//                title = :title
//                WHERE id = :id"
    );


    $statement->execute();
//        [
//        'id' => $post_id,
//        'title' => $title,
//        'content' => $content
//    ]


    return $statement->fetchAll();
}





}


//    public function getRole(Request $request, Response $response, array $args): Response
//    {
//        error_log('Распределение ролей');
//        $args = [];
//        $args['User'] = [0];
//        $args['admin'] = [1];
//        $args['helper'] = [2];
//        $connection = $this->dataBase->getConnection();
//        $statement = $connection->prepare(
//            ' SELECT * FROM user WHERE role = 0'
//        );
//        $statement->execute();
//
//        $role = $statement->fetchAll();
//
//        return $response;
//    }