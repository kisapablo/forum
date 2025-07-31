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

        $statement = $connection->prepare( // Если не робит то добавь перед getConnection, dataBase->
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


    if ($statement->execute()) {
        header("Location: /");
    }

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

}