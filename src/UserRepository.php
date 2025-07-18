<?php

namespace Blog;

class UserRepository
{
    private DataBase $dataBase;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    public function addUser($login, $password, $role = 0)
    {
        $salt = generateSalt();
        $hash = generateHash($salt, $password);

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'INSERT INTO user (name, password_hash, password_salt, role) 
            values (:login, :hash, :salt, :role)
            ',
        );

        $result = $statement->execute([
            'login' => $login,
            'hash' => $hash,
            'salt' => $salt,
            'role' => $role
        ]);

        if (!$result) {
            return null;
        }


        return $this->findUserByLoginAndPassword($login, $password);
    }

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
            //todo: raise exception UserNotFound
            error_log('No user is found');
            return null;
        }

        $user = $users[0];
        //todo: add logic to compute hash

        $hash = generateHash($user['password_salt'], $password);

        if ($user['password_hash'] != $hash) {
            error_log('Invalid user password');
            //todo: raise exception InvalidUserPassword
            return null;
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
}

//fixme
function generateSalt()
{
    return '64';
}

//fixme
function generateHash($salt, $password)
{
    return $password;
}