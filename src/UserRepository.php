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

    public function addUser($login, $password, $role = 1, $icon = null)
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
            'icon_id' => $icon
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

    public function findUserIcon($userId)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'select u.id as user_id, uiv.icon_name as icon_name from user as u
                    join user_icon_view uiv on u.icon_id = uiv.id
                    where u.id = :user_id'
        );

        $statement->execute([
            'user_id' => $userId
        ]);

        $icons = $statement->fetchAll();

        if (empty($icons)) {
            return null;
        }

        return $icons[0];
    }

     public function cabinetInfo($userId)
    {

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
       // 'select * from user_info_view where user_id = :user_id;  '
       'SELECT
        u.role_id, u.id as user_id, u.last_visit_date, u.registration_date, u.moto,
        r.en_name, r.ru_name, count(p.id) as total
        from `user` u
        join role r on r.id = u.role_id
        left join post p on u.id = p.author_id
        WHERE u.id = :user_id
        GROUP BY p.author_id;' 
        );
        $statement->execute([
            'user_id' => $userId
        ]);
        return $statement->fetch();
    }
    /*
    public function findPostIcon($userId)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'select u.id as user_id, uiv.icon_name as icon_name from user as u
                    join user_icon_view uiv on u.icon_id = uiv.id
                    where u.id = :user_id'
        );

        $statement->execute([
            'user_id' => $userId
        ]);

        return $statement->fetchAll();

    }
*/
    public function saveUserIcon($fileName, $userId)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "call ADDUserIcon(:fileName, :userId, FALSE, @attachment_id); select @attachment_id;"
        );
        $statement->bindParam('fileName', $fileName);
        $statement->bindParam('userId', $userId);

        $statement->execute();
        $iconId = $statement->fetchAll()[0];
        error_log('Icon Id = ' . $iconId);
        return $statement->fetchAll();
    }

    public function setUserIcon($iconId, $userId)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            "UPDATE user SET icon_id = :defaultIcon WHERE id = :userId;"
        );
        $statement->bindParam('defaultIcon', $iconId);
        $statement->bindParam('userId', $userId);

        $statement->execute();
        return $statement->fetchAll();
    }

    public function getdefaultIcon()
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'SELECT * from user_icon_view where is_default = 1'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
    //    public function FindCommentAuthorIcon ( int $authorID)
    //    {
    //        $connection = $this->dataBase->getConnection();
    //
    //        $statement = $connection->prepare(
    //            'SELECT * from user_icon_view where user_id = :author_id;'
    //        );
    //
    //        $statement->execute([
    //            'author_id' => $authorID
    //        ]);
    //
    //        return $statement->fetchAll();
    //
    //    }

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
