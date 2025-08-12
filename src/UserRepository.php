<?php

namespace Blog;

use Exception;
use PDO;

class UserRepository
{
    private DataBase $dataBase;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    public function addUser($login, $password, $role = 0)
    {
        $salt = $this->generateSalt();
        $hash = $this->generateHash($salt, $password);

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'INSERT INTO user (name, password_hash, password_salt, role_id, icon_id, registration_date)  
            values (:login, :hash, :salt, :role_id, :icon_id, CURRENT_DATE)
            ',
        );

        $result = $statement->execute([
            'login' => $login,
            'hash' => $hash,
            'salt' => $salt,
            'role_id' => $role,
            'icon_id' => null
        ]);

        if (!$result) {
            return null;
        }


        return $this->findUserByLoginAndPassword($login, $password);
    }

    /**
     * @throws Exception
     */
    public function findUserByLoginAndPassword($login, $password)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare('SELECT * from user where name = :login');

        $result = $statement->execute([
            'login' => $login
        ]);

        if (!$result) {
            return null;
        }

        $users = $statement->fetchAll();

        if (empty($users)) {
            error_log('No user is found');
            throw new Exception('User is not found');
        }

        $user = $users[0];
        //todo: add logic to compute hash

        $hash = $this->generateHash($user['password_salt'], $password);

        if ($user['password_hash'] != $hash) {
            error_log('Invalid user password');
            throw new Exception('User password is not valid');
        }

        return $user;
    }

//    private function getUsers()
//    {
//        $content = file_get_contents($this->filePath);
//        return json_decode($content, true) ?: [];
//    }
//
//    private function saveUsers($users): bool|int
//    {
//        return file_put_contents($this->filePath, json_encode($users, JSON_PRETTY_PRINT));
//    }
//
//    public function getAllUsers()
//    {
//        return $this->getUsers();
//    }


    public function findUserIcon($user_id)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * from user_icon_view where user_id = :user_id;'
        );

        $statement->execute([
            'user_id' => $user_id
        ]);

        return $statement->fetchAll();

    }

    public function saveUserIcon($fileName, $userId)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'call ADDUserIcon(:fileName, :userId, TRUE, @id); select $id'
        );
        $statement->bindParam('fileName', $fileName);
        $statement->bindParam('userId', $userId);

        $statement->execute();
        $iconId =  $statement->fetchAll()[0];
        error_log('Icon Id = '. $iconId);
        return $statement->fetchAll();

    }

    public function savedefaulticon($defaultIcon, $userId)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'call ADDUserIcon(:defaultIcon, :userId, TRUE, @id); select $id'
        );
        $statement->bindParam('defaultIcon', $defaultIcon);
        $statement->bindParam('userId', $userId);

        $statement->execute();
        $iconId =  $statement->fetchAll()[0];
        error_log('Icon Id = '. $iconId);
        return $statement->fetchAll();

    }

    public function findAuthorIcon($postid)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * from user_icon_view where user_id = :post_id;'
        );

        $statement->execute([
            'post_id' => $postid
        ]);

        return $statement->fetchAll();

    }
    public function FindCommentAuthorIcon ( int $authorID)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * from user_icon_view where user_id = :author_id;'
        );

        $statement->execute([
            'author_id' => $authorID
        ]);

        return $statement->fetchAll();

    }

//fixme
    function generateSalt()
    {
        return '64';
    }

//fixme
    function generateHash($salt, $password)
    {

// check for have unhashing password

//    $checkhash = // PDO fetch pass in findUserByLoginAndPassword and form authentication
//    if (!empty($login) && !empty($password)) { view on all pass for unhashing else unhashing pass found drop and return for not found pass
// generate hash
//    }

// return result all

        return $password;
    }

}
