<?php

namespace Blog;

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

        return (int)($statement->fetchColumn() ?? 0);
    }

    public function getTotalCountUsers($userId): int
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare( // Если не робит то добавь перед getConnection, dataBase-> неактуально делай через $connection
            'SELECT count(id) as total FROM post where author_id = :author_id'
        );


        $statement->execute([
            'author_id' => $userId
        ]);

        return (int)($statement->fetchColumn() ?? 0);
    }

    public function addNewPost($title, $content, $id)
    {
        $connection = $this->dataBase->getConnection();


        // Вбивание данных из шаблонов
        $statement = $connection->prepare(
            "INSERT INTO post (title, content, author_id, publication_date) 
                VALUES (:title, :content, :id, CURRENT_DATE)"
        );
        $statement->execute([
            'title' => $title,
            'content' => $content,
            'id' => $id
        ]);

        return $connection->lastInsertId();
    }


    public function getNewPostID()
    {

    }

    public function savePostAttachment($id, $fileName)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "call ADDPostAttachment(:fileName, :userId, @post_attachment_id); select @post_attachment_id;"
        );

        $statement->bindParam('userId', $id);
        $statement->bindParam('fileName', $fileName);
        $result = $statement->execute();
        return $statement->fetchAll();
    }

    public function findAllPosts($limit, $start)
    {
        // Проверяем переменная обьявлена ли и разницу с null

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * FROM user_post_view ORDER BY publication_date DESC LIMIT :limit OFFSET :start' //:start
        );

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('start', $start, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findPostById($post_id)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * FROM post where id = :id'
        );

        $statement->execute([
            'id' => $post_id
        ]);

        $posts = $statement->fetchAll();

        if (empty($posts)) {
            return null;
        }

        return $posts[0];
    }

    public function findCommentById($comment_id)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * from user_comment_view where id = :id'
        );

        $statement->execute([
            'id' => $comment_id
        ]);

        $posts = $statement->fetchAll();

        if (empty($posts)) {
            return null;
        }

        return $posts[0];
    }

    public function updatePosts($title, $content, $post_id)
    {
        $connection = $this->dataBase->getConnection();


        // Изменение данных из шаблонов
        $statement = $connection->prepare(
            "UPDATE post SET
                content = :content,
                title = :title
                WHERE id = :post_id");


        $statement->execute([
            'post_id' => $post_id,
            'title' => $title,
            'content' => $content
        ]);


        return $statement->fetchAll();
    }

    public function updateComments($content, $comment_id)
    {
        $connection = $this->dataBase->getConnection();


        // Изменение данных из шаблонов
        $statement = $connection->prepare(
            "UPDATE comment SET
                content = :content
                WHERE id = :comment_id");


        $statement->execute([
            'comment_id' => $comment_id,
            'content' => $content
        ]);


        return $statement->fetchAll();
    }

    public function findAllPostsByAuthorId($authorId, $limit, $start): array
    {
        // Проверяем переменная обьявлена ли и разницу с null

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
//            'SELECT * FROM post where author_id = :id' //
            'SELECT * FROM user_post_view WHERE author_id = :author_id ORDER BY publication_date LIMIT :limit OFFSET :start'
        );

        $statement->bindValue(':author_id', $authorId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('start', $start, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function getPostAttachmentView($post_id) //
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'select * from post_attachment_view where post_id = :post_id' //
        );

        $statement->execute([
            'post_id' => $post_id
        ]);

        return $statement->fetchAll();
    }

    public function getAllPostTag($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $in  = str_repeat('?,', count($ids) - 1) . '?';


        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "select * from post_tag_view where post_id IN ($in);"
        );

        $statement->execute($ids);

        return $statement->fetchAll(    PDO::FETCH_ASSOC);
    }
    public function getAllPostWhereTag($ids)
    {
  /*
        if (empty($ids)) {
            return [];
        }

        $in  = str_repeat('?,', count($ids) - 1) . '?';
*/

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
                "select * from search_tag_view;"
        );

        //$statement->execute($ids);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deletePost($post_id)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "DELETE FROM `post` WHERE `id` = :id;"
        );

        $statement->execute([
            'id' => $post_id
        ]);
        return $statement->fetchAll();
    }
    public function deleteAllPostComment($post_id)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "DELETE FROM `comment` WHERE `post_id` = :id;"
        );

        $statement->execute([
//            'id' => $comment_id
        'id' => $post_id
        ]);
        return $statement->fetchAll();
    }

    public function deleteComment($comment_id)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "DELETE FROM `comment` WHERE `id` = :id;"
        );

        $statement->execute([
            'id' => $comment_id
        ]);
        return $statement->fetchAll();
    }
}