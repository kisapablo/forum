<?php

namespace Blog;

class UserRepository
{
    private string $filePath = 'users.json';

    public function __construct()
    {
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    public function addUser($login, $password)
    {
        $users = $this->getUsers();

        // Проверка на уникальность логина
        foreach ($users as $user) {
            if ($user['login'] === $login) {
                return null; // Логин уже существует
            }
        }

        // Генерация нового ID
        $newId = empty($users) ? 1 : max(array_keys($users)) + 1;

        // Добавление пользователя
        $users[$newId] = [
            'id' => $newId,
            'login' => $login,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        $this->saveUsers($users);

        return $users[$newId];
    }

    public function verifyUser($login, $password)
    {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['login'] === $login && password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    private function getUsers()
    {
        $content = file_get_contents($this->filePath);
        return json_decode($content, true) ?: [];
    }

    private function saveUsers($users): bool|int
    {
        return file_put_contents($this->filePath, json_encode($users, JSON_PRETTY_PRINT));
    }

    public function getAllUsers()
    {
        return $this->getUsers();
    }
}

