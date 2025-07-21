<?php

namespace Blog;

use Exception;

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

        $hash = generateHash($user['password_salt'], $password);

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