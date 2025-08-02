<?php

namespace Blog;

use Exception;

class CommentRepository
{
    private DataBase $dataBase;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    function getAllComments(int $postId)
    {
        error_log('Start to connection for DataBase(Check Comments)');
        $connection = $this->dataBase->getConnection();
        error_log('fetch comments');
        $statement = $connection->prepare(
            "SELECT * from comment where post_id = :post_id"
        );

        $statement->execute([
            "post_id" => $postId
        ]);

        return $statement->fetchAll();
    }

    /**
     * @throws Exception
     */
    public function createComment(array $comment)
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
            'author_id' => $comment['author_id']
        ]);

        if (!$result) {
            error_log('Incorrect comment');
            throw new Exception('Incorrect comment');
        }
    }
    public function getCommentAttachmentView() //
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'select * from comment_attachment_view' //
        );

        $statement->execute();

        return $statement->fetchAll();

    }
}
