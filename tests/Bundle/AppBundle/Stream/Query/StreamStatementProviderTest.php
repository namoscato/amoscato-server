<?php

namespace Tests\Bundle\AppBundle\Stream\Query;

use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Mockery as m;

class StreamStatementProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var m\Mock
     */
    private $database;

    /**
     * @var StreamStatementProvider
     */
    private $streamStatementProvider;

    protected function setUp()
    {
        $this->database = m::mock('PDO');

        $this->streamStatementProvider = new StreamStatementProvider($this->database);
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_insertRows()
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

        $this->database
            ->shouldReceive('prepare')
            ->once()
            ->with($sql)
            ->andReturn('stmt');

        $this->assertSame(
            'stmt',
            $this->streamStatementProvider->insertRows(2)
        );
    }

    public function test_selectLatestSourceId()
    {
        $sql = <<<SQL
SELECT source_id
FROM stream
WHERE type = :type
ORDER BY created_at DESC, id DESC
LIMIT :limit;
SQL;

        $statement = m::mock('PDOStatement',
            function($mock) {
                /** @var m\Mock $mock */

                $mock
                    ->shouldReceive('bindValue')
                    ->once()
                    ->with(
                        ':type',
                        'TYPE'
                    );

                $mock
                    ->shouldReceive('bindValue')
                    ->once()
                    ->with(
                        ':limit',
                        1,
                        1
                    );
            }
        );

        $this->database
            ->shouldReceive('prepare')
            ->once()
            ->with($sql)
            ->andReturn($statement);

        $this->assertSame(
            $statement,
            $this->streamStatementProvider->selectLatestSourceId('TYPE')
        );
    }

    public function test_selectStreamRows()
    {
        $sql = <<<SQL
SELECT *
FROM stream
WHERE type = :type
ORDER BY created_at DESC, id DESC
LIMIT :limit;
SQL;

        $statement = m::mock('PDOStatement',
            function($mock) {
                /** @var m\Mock $mock */

                $mock
                    ->shouldReceive('bindValue')
                    ->once()
                    ->with(
                        ':type',
                        'TYPE'
                    );

                $mock
                    ->shouldReceive('bindValue')
                    ->once()
                    ->with(
                        ':limit',
                        10,
                        1
                    );
            }
        );

        $this->database
            ->shouldReceive('prepare')
            ->once()
            ->with($sql)
            ->andReturn($statement);

        $this->assertSame(
            $statement,
            $this->streamStatementProvider->selectStreamRows('TYPE', 10)
        );
    }
}
