<?php


namespace Blog;

use Exception;
use PDO;

class PostMapper
{
    /**
     * @var DataBase
     */
    private DataBase $dataBase;

    /**
     * @param DataBase $dataBase
     */
    public function __construct (DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    /**
     * @param string $urlKey
     * @return array|null
     */
    public function getByUrlKey (string $urlKey): ?array
    {
        $statement = $this->getConnection()->prepare('SELECT * FROM post WHERE url_key = :url_key'); // Если не робит то добавь перед getConnection, dataBase->
        $statement->execute([
            'url_key' => $urlKey
        ]);
        $result = $statement-> fetchAll();

        return array_shift($result);
    }

    /**
     * @param int $page
     * @param int $limit
     * @param string $direction
     * @return array|null
     * @throws Exception
     */

    public function getList(int $page = 1, int $limit = 2, string $direction = 'ASC'): ?array
    {
        if (!in_array($direction, ['DESC', 'ASC'])) {
            throw new Exception('The direction is not supported.');
        }


            $start = ($page - 1) * $limit;
            $statement = $this->getConnection()->prepare( // Если не робит то добавь перед getConnection, dataBase->
                'SELECT * FROM post ORDER BY published_date ' . $direction .
                ' LIMIT ' . $start . ',' . $limit
            );

                $statement->execute();

            return $statement->fetchAll();
    }

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
$statement = $this->getConnection()->prepare( // Если не робит то добавь перед getConnection, dataBase->
    'SELECT count(post_id) as total FROM post'
);


$statement->execute();

return (int) ($statement->fetchColumn() ?? 0);
    }

    /**
     * @return PDO
     */
    private function getConnection(): PDO
    {
        return $this->dataBase->getConnection();
    }
}