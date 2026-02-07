<?php

namespace Blog;

use Exception;
use PDO;

class CommentRepository
{
    private DataBase $dataBase;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    function findUserComments(int $postId)
    {
        $connection = $this->dataBase->getConnection();
        $statement = $connection->prepare(
            "SELECT * from user_comment_view where post_id = :post_id"
        );

        $statement->execute([
            "post_id" => $postId
        ]);

        return $statement->fetchAll();
    }

    function findWhereComments(int $comment_id)
    {
        $connection = $this->dataBase->getConnection();
        $statement = $connection->prepare(
            "SELECT * from user_comment_view where id = :id"
        );

        $statement->execute([
            "id" => $comment_id
        ]);

        $posts = $statement->fetchAll();

        if (empty($posts)) {
            return null;
        }

        return $posts[0];
    }

    /**
     * @throws Exception
     */
    public function createComment(array $comment, $fileName, $userId)
    {
        error_log('Start to connect on the DataBase(Create Comments)');
        $connection = $this->dataBase->getConnection();
        error_log('INSERT comments');
        // Вбивание данных из шаблона
        $statement = $connection->prepare(
            "INSERT INTO comment (content, author_id, post_id) 
                VALUES ( :content, :author_id, :post_id )"
        );

        $result = $statement->execute([
            'content' => $comment['content'],
            'post_id' => $comment['post_id'],
            'author_id' => $comment['author_id'],
        ]);

        if (!$result) {
            error_log('Incorrect comment');
            throw new Exception('Incorrect comment');
        }

        return $connection->lastInsertId();
//        return $statement->fetchAll()[0];
    }

    public function saveCommentAttachment($userId, $fileName)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "call ADDCommentAttachment(:fileName, :userId, @post_attachment_id); select @post_attachment_id;"
        );

        $statement->bindParam('userId', $userId);
        $statement->bindParam('fileName', $fileName);
        $result = $statement->execute();
        return $statement->fetchAll();
    }

    public function findCommentAttachmentsByIds(array $ids) //
    {
        if (empty($ids)) {
            return [];
        }

        $connection = $this->dataBase->getConnection();

        $in  = str_repeat('?,', count($ids) - 1) . '?';

        $statement = $connection->prepare(
            "select * from comment_attachment_view where comment_id in ($in)"
        );

        $statement->execute($ids);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
