<?php

namespace Amoscato\Bundle\AppBundle\Stream\Query;

use PDO;

class PhotoStatementProvider
{
    /**
     * @var PDO
     */
    private $database;

    private static $insertionSql = <<<SQL
INSERT INTO photo (
  type,
  source_id,
  url,
  width,
  height,
  title,
  reference_url
) VALUES %s;
SQL;

    /**
     * @var string
     */
    private static $insertionRowSql;

    /**
     * @param PDO $database
     */
    public function __construct(PDO $database)
    {
        $this->database = $database;

        self::$insertionRowSql = '(' . implode(', ', array_fill(0, 7, '?')) . ')';
    }

    /**
     * @param integer $rowCount
     * @return \PDOStatement
     */
    public function insertRows($rowCount)
    {
        return $this->database->prepare(
            sprintf(
                self::$insertionSql,
                implode(', ', array_fill(0, $rowCount, self::$insertionRowSql))
            )
        );
    }
}
