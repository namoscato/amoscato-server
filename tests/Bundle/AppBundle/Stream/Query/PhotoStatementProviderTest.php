<?php

namespace Tests\Bundle\AppBundle\Stream\Query;

use Amoscato\Bundle\AppBundle\Stream\Query\PhotoStatementProvider;
use Mockery as m;

class PhotoStatementProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var m\Mock
     */
    private $database;

    /**
     * @var PhotoStatementProvider
     */
    private $photoStatementProvider;

    protected function setUp()
    {
        $this->database = m::mock('PDO');

        $this->photoStatementProvider = new PhotoStatementProvider($this->database);
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
            $this->photoStatementProvider->insertRows(2)
        );
    }
}
