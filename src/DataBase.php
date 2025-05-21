<?php

namespace Blog;

use http\Exception\InvalidArgumentException;
use PDO;
use PDOException;

class DataBase
{
    /**
     * @var PDO
     */
    private PDO $connection;

    /**
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     */
    public function __construct(string $dsn, string $username = null, string $password = null)
    {
        try{
           $this->connection = new PDO($dsn, $username, $password); // mysql
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //mysql
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); //mysql
        } catch (PDOException $exception) {   // mysql
            throw new InvalidArgumentException($exception->getMessage());
        } // mysql end
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}