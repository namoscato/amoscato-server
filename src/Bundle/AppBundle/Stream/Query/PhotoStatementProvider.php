<?php

namespace Amoscato\Bundle\AppBundle\Stream\Query;

use PDO;

class PhotoStatementProvider
{
    /** @var PDO */
    private $database;

    /** @var string */
    private static $insertionSql = <<<SQL
INSERT INTO photo (
  type,
  source_id,
  url,
  width,
  height,
  title,
  reference_url
) VALUES %s
ON CONFLICT ON CONSTRAINT photo_type_source_id DO UPDATE SET
  url = EXCLUDED.url,
  width = EXCLUDED.width,
  height = EXCLUDED.height,
  title = EXCLUDED.title,
  reference_url = EXCLUDED.reference_url;
SQL;

    /** @var string */
    private static $selectLatestSourceIdSql = <<<SQL
SELECT source_id
FROM photo
WHERE type = :type
ORDER BY id DESC
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

    /**
     * @return \PDOStatement
     */
    public function selectLatestSourceId()
    {
        return $this->database->prepare(self::$selectLatestSourceIdSql);
    }
}
