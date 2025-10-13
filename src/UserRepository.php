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

    public function addUser($login, $password, $role = 0, $icon = null)
    {
        $salt = $this->generateSalt();
        $hash = $this->generateHash($salt, $password);

        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'INSERT INTO user (name, password_hash, password_salt, role_id, icon_id, registration_date, last_visit_date)  
            values (:login, :hash, :salt, :role_id, :icon_id, CURRENT_TIME, CURRENT_TIME)
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


        return $this->findUserByLoginAndPassword($login, $password, $hash);
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

        // password_verify($password, $hash);

        if (empty($users)) {
            error_log('No user is found');
            throw new Exception('User is not found');
        }

        $user = $users[0];
        //todo: add logic to compute hash

        $salt = $user['password_salt'];

        $hash = $this->generateHash($salt, $password);

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

    public function updateUserInfo($moto, $user_Id)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'UPDATE user SET moto = :moto WHERE user.id = :user_id;'
        );
        $statement->execute([
            'moto' => $moto,
            'user_id' => $user_Id
        ]);
        return $statement->fetchAll();
    }

    public function updateUserName($UserID, $NewNickName)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare(
            'UPDATE `user` SET `name` = :NickName WHERE id = :UserID'
        );

        $statement->execute([
            'UserID' => $UserID,
            'NickName' => $NewNickName
        ]);

        return $statement->fetchAll();
    }

    public function UpdatePasswordHash($password, $UserID)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare('UPDATE `user` SET `password_hash` = :password WHERE id = :UserID;');

        $statement->execute([
            'password' => $password,
            'UserID' => $UserID
        ]);
    }

    public function Updateactivity($UserID)
    {
        $connection = $this->dataBase->getConnection();

        $statement = $connection->prepare('UPDATE `user` SET last_visit_date = CURRENT_TIME WHERE id = :UserID;');

        $statement->execute([
            'UserID' => $UserID
        ]);
    }


    //fixme
    function generateSalt()
    {
        return '64';
    }

    //fixme
    function generateHash($salt, $password)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);

        return $password;
    }
}
