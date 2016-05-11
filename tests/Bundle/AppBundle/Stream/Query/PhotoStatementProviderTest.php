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
INSERT INTO photo (
  type,
  source_id,
  url,
  width,
  height,
  title,
  reference_url
) VALUES (?, ?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?, ?)
ON CONFLICT ON CONSTRAINT photo_type_source_id DO UPDATE SET
  url = EXCLUDED.url,
  width = EXCLUDED.width,
  height = EXCLUDED.height,
  title = EXCLUDED.title,
  reference_url = EXCLUDED.reference_url;
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
