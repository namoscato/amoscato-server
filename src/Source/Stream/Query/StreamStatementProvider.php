<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream\Query;

use PDO;
use PDOStatement;

class StreamStatementProvider
{
    /** @var PDO */
    private $database;

    public function __construct(PDO $database)
    {
        $this->database = $database;
    }

    /**
     * @param int $rowCount
     *
     * @return bool|PDOStatement
     */
    public function insertRows($rowCount)
    {
        $sql = <<<SQL
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

        return $this->database->prepare(
            sprintf(
                $sql,
                implode(
                    ', ',
                    array_fill(0, $rowCount, sprintf('(%s)', implode(', ', array_fill(0, 8, '?'))))
                )
            )
        );
    }

    /**
     * @param string $type
     *
     * @return PDOStatement
     */
    public function selectLatestSourceId($type)
    {
        return $this->selectStreamRows($type, 1, 'source_id');
    }

    /**
     * @param string $type
     * @param int $limit
     * @param string $select optional
     *
     * @return bool|PDOStatement
     */
    public function selectStreamRows($type, $limit, $select = '*')
    {
        $sql = <<<SQL
SELECT %s
FROM stream
WHERE type = :type
ORDER BY created_at DESC, id DESC
LIMIT :limit;
SQL;
        $statement = $this->database->prepare(sprintf($sql, $select));

        $statement->bindParam(':type', $type);
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);

        return $statement;
    }

    /**
     * @param string $type
     * @param int $offset
     */
    public function selectCreatedDateAtOffset($type, $offset): string
    {
        $sql = <<<SQL
SELECT created_at
FROM stream
WHERE type = :type
ORDER BY created_at DESC
OFFSET :offset
LIMIT 1;
SQL;
        $stmt = $this->database->prepare($sql);

        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * @param string $type
     * @param string $createdAt
     */
    public function deleteOldItems($type, $createdAt): bool
    {
        $stmt = $this->database->prepare('DELETE FROM stream WHERE type = :type AND created_at < :createdAt;');

        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':createdAt', $createdAt);

        return $stmt->execute();
    }
}
