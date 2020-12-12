<?php

declare(strict_types=1);

namespace Tests\Source\Stream\Query;

use Amoscato\Source\Stream\Query\StreamStatementProvider;
use Mockery as m;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class StreamStatementProviderTest extends TestCase
{
    /** @var m\Mock */
    private $database;

    /** @var StreamStatementProvider */
    private $target;

    protected function setUp(): void
    {
        $this->database = m::mock('PDO');

        $this->target = new StreamStatementProvider($this->database);
    }

    public function testInsertRows(): void
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
) VALUES (?, ?, ?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?, ?, ?)
ON CONFLICT ON CONSTRAINT stream_type_source_id DO UPDATE SET
  title = EXCLUDED.title,
  url = EXCLUDED.url,
  created_at = EXCLUDED.created_at,
  photo_url = EXCLUDED.photo_url,
  photo_width = EXCLUDED.photo_width,
  photo_height = EXCLUDED.photo_height;
SQL;

        $this
            ->database
            ->shouldReceive('prepare')
            ->once()
            ->with($sql)
            ->andReturn('stmt');

        self::assertSame(
            'stmt',
            $this->target->insertRows(2)
        );
    }

    public function testSelectLatestSourceId(): void
    {
        $sql = <<<SQL
SELECT source_id
FROM stream
WHERE type = :type
ORDER BY created_at DESC, id DESC
LIMIT :limit;
SQL;

        $statement = m::mock('PDOStatement',
            static function ($mock) {
                /* @var m\Mock $mock */

                $mock
                    ->shouldReceive('bindParam')
                    ->once()
                    ->with(
                        ':type',
                        'TYPE'
                    );

                $mock
                    ->shouldReceive('bindParam')
                    ->once()
                    ->with(
                        ':limit',
                        1,
                        1
                    );
            }
        );

        $this
            ->database
            ->shouldReceive('prepare')
            ->once()
            ->with($sql)
            ->andReturn($statement);

        self::assertSame(
            $statement,
            $this->target->selectLatestSourceId('TYPE')
        );
    }

    public function testSelectStreamRows(): void
    {
        $sql = <<<SQL
SELECT *
FROM stream
WHERE type = :type
ORDER BY created_at DESC, id DESC
LIMIT :limit;
SQL;

        $statement = m::mock('PDOStatement',
            static function ($mock) {
                /* @var m\Mock $mock */

                $mock
                    ->shouldReceive('bindParam')
                    ->once()
                    ->with(
                        ':type',
                        'TYPE'
                    );

                $mock
                    ->shouldReceive('bindParam')
                    ->once()
                    ->with(
                        ':limit',
                        10,
                        1
                    );
            }
        );

        $this
            ->database
            ->shouldReceive('prepare')
            ->once()
            ->with($sql)
            ->andReturn($statement);

        self::assertSame(
            $statement,
            $this->target->selectStreamRows('TYPE', 10)
        );
    }

    public function testSelectCreatedDateAtOffset(): void
    {
        $this
            ->database
            ->shouldReceive('prepare')
            ->andReturn(m::mock(
                PDOStatement::class,
                static function ($stmt) {
                    /* @var m\Mock $stmt */

                    $stmt
                        ->shouldReceive('bindParam')
                        ->once()
                        ->with(':type', 'TYPE');

                    $stmt
                        ->shouldReceive('bindParam')
                        ->once()
                        ->with(':offset', 10, PDO::PARAM_INT);

                    $stmt
                        ->shouldReceive('execute')
                        ->once();

                    $stmt
                        ->shouldReceive('fetchColumn')
                        ->andReturn('DATE');
                }
            ));

        self::assertEquals('DATE', $this->target->selectCreatedDateAtOffset('TYPE', 10));
    }

    public function testDeleteOldItems(): void
    {
        $this
            ->database
            ->shouldReceive('prepare')
            ->andReturn(m::mock(
                PDOStatement::class,
                static function ($stmt) {
                    /* @var m\Mock $stmt */

                    $stmt
                        ->shouldReceive('bindParam')
                        ->once()
                        ->with(':type', 'TYPE');

                    $stmt
                        ->shouldReceive('bindParam')
                        ->once()
                        ->with(':createdAt', 'DATE');

                    $stmt
                        ->shouldReceive('execute')
                        ->andReturnTrue();
                }
            ));

        self::assertTrue($this->target->deleteOldItems('TYPE', 'DATE'));
    }
}
