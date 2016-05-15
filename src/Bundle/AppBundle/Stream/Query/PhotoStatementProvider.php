<?php

namespace Amoscato\Bundle\AppBundle\Stream\Query;

use PDO;

class PhotoStatementProvider
{
    /** @var PDO */
    private $database;

    /** @var string */
    private static $insertionSql = <<<SQL
INSERT INTO stream (
  type,
  source_id,
  title,
  url,
  created_at,
  photo_url,
  photo_width,
  photo_height
) VALUES %s
ON CONFLICT ON CONSTRAINT stream_type_source_id DO UPDATE SET
  title = EXCLUDED.title,
  url = EXCLUDED.url,
  created_at = EXCLUDED.created_at,
  photo_url = EXCLUDED.photo_url,
  photo_width = EXCLUDED.photo_width,
  photo_height = EXCLUDED.photo_height;
SQL;

    /** @var string */
    private static $selectLatestSourceIdSql = <<<SQL
SELECT source_id
FROM stream
WHERE type = :type
ORDER BY created_at DESC, id DESC
LIMIT 1;
SQL;

    /** @var string */
    private static $insertionRowSql;

    /**
     * @param PDO $database
     */
    public function __construct(PDO $database)
    {
        $this->database = $database;

        self::$insertionRowSql = '(' . implode(', ', array_fill(0, 8, '?')) . ')';
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

    /**
     * @return \PDOStatement
     */
    public function selectLatestSourceId()
    {
        return $this->database->prepare(self::$selectLatestSourceIdSql);
    }
}
