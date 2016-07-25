<?php

namespace Amoscato\Bundle\AppBundle\Stream\Query;

use PDO;

class StreamStatementProvider
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
    private static $selectStreamTypeSql = <<<SQL
SELECT %s
FROM stream
WHERE type = :type
ORDER BY created_at DESC, id DESC
LIMIT :limit;
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
     * @param string $type
     * @return \PDOStatement
     */
    public function selectLatestSourceId($type)
    {
        return $this->selectStreamRows($type, 1, 'source_id');
    }

    /**
     * @param string $type
     * @param int $limit
     * @param string $select optional
     * @return \PDOStatement
     */
    public function selectStreamRows($type, $limit, $select = '*')
    {
        $statement = $this->database->prepare(
            sprintf(
                self::$selectStreamTypeSql,
                $select
            )
        );

        $statement->bindValue(':type', $type);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);

        return $statement;
    }
}
