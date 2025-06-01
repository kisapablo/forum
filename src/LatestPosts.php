<?php

namespace Blog;
class LatestPosts
{
    /**
     * @var DataBase
     */

    private DataBase $DataBase;

    /** LatestPosts const
     * @param DataBase $DataBase
     */
    public function __construct(DataBase $DataBase)
    {
        $this->DataBase = $DataBase;
}

    /**
     * @param int $limit
     * @return array|null
     */
public function get(int $limit): ?array
{
    $statement = $this->DataBase->getConnection()->prepare(
        'SELECT * FROM post ORDER BY  published_date DESC LIMIT ' . $limit
    );

    $statement->execute();

    return $statement->fetchAll();
}
}