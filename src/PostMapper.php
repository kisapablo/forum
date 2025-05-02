<?php


namespace Blog;

class PostMapper
{
    /**
     * @var /PDO
     */
    private \PDO $connection;

    /** // PostMapper constructor
     * @param \PDO $connection
     */
    public function __construct (\PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $urlKey
     * @return array
     */
    public function getByUrlKey (string $urlKey): array
    {
        $statement = $this->connection->prepare('SELECT * FROM post WHERE url_key = :url_key');
        $statement->execute([
            'url_key' => $urlKey
        ]);
        $result = $statement-> fetchAll();

        return array_shift($result);
    }
}