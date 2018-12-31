<?php

namespace Tests\Source\Stream;

use Amoscato\Ftp\FtpClient;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use Amoscato\Source\Stream\UntappdSource;
use Amoscato\Integration\Client\UntappdClient;
use Amoscato\Console\Output\ConsoleOutput;
use Amoscato\Database\PDOFactory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class UntappdSourceTest extends TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var UntappdSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock(UntappdClient::class);
        
        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider]', UntappdSource::class),
            [
                m::mock(PDOFactory::class),
                m::mock(FtpClient::class),
                $this->client,
                'username'
            ]
        );

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            ConsoleOutput::class,
            [
                'writeln' => null,
                'writeVerbose' => null
            ]
        );
    }

    protected function tearDown()
    {
        $this->addToAssertionCount(m::getContainer()->mockery_getExpectationCount());
        m::close();
    }

    public function test_load()
    {
        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('untappd')
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock->shouldReceive('execute');

                    $mock
                        ->shouldReceive('fetch')
                        ->andReturn(
                            [
                                'source_id' => '10'
                            ]
                        );
                })
            );

        $this
            ->client
            ->shouldReceive('getUserBadges')
            ->with(
                'username',
                [
                    'offset' => 0,
                    'limit' => 50
                ]
            )
            ->andReturn(
                (object) [
                    'items' => [
                        (object) [
                            'badge_name' => 'badge',
                            'user_badge_id' => 'id',
                            'created_at' => '2018-05-13 12:00:00',
                            'media' => (object) [
                                'badge_image_lg' => 'img.jpg'
                            ]
                        ]
                    ]
                ]
            );

        $this
            ->client
            ->shouldReceive('getUserBadges')
            ->with(
                'username',
                [
                    'offset' => 50,
                    'limit' => 50
                ]
            )
            ->andReturn(
                (object) [
                    'items' => []
                ]
            );

        $this
            ->client
            ->shouldReceive('getBadgeUrl')
            ->with(
                'username',
                'id'
            )
            ->andReturn('badge url');

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(1)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->with(m::mustBe([
                            'untappd',
                            'id',
                            'badge',
                            'badge url',
                            '2018-05-13 12:00:00',
                            'img.jpg',
                            400,
                            400
                        ]));
                })
            );

        $this->source->load($this->output, 100);
    }
}
