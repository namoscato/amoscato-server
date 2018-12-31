<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Amoscato\Bundle\AppBundle\Stream\Source\GoodreadsSource;
use Amoscato\Bundle\IntegrationBundle\Client\GoodreadsClient;
use Amoscato\Console\Output\ConsoleOutput;
use Amoscato\Database\PDOFactory;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class GoodreadsSourceTest extends TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var GoodreadsSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock(GoodreadsClient::class);
        
        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider,createCrawler,getImageSize]', GoodreadsSource::class),
            [
                m::mock(PDOFactory::class),
                m::mock(FtpClient::class),
                $this->client,
                10
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
                'writeVerbose' => null,
            ]
        );
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load()
    {
        $this
            ->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('goodreads')
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
            ->shouldReceive('getReadBooks')
            ->with(
                10,
                [
                    'page' => 1,
                    'per_page' => 100,
                    'sort' => 'date_read'
                ]
            )
            ->andReturn(
                [
                    'item1',
                    'item2'
                ]
            )
            ->shouldReceive('getReadBooks')
            ->andReturn([]);

        $this
            ->source
            ->shouldReceive('createCrawler')
            ->with('item1')
            ->andReturn(
                m::mock(
                    Crawler::class,
                    function($mock) {
                        /** @var m\Mock $mock */

                        $mock
                            ->shouldReceive('filter')
                            ->with('id')
                            ->andReturn(
                                m::mock(
                                    [
                                        'text' => 1
                                    ]
                                )
                            );

                        $mock
                            ->shouldReceive('filter')
                            ->with('book')
                            ->andReturn(
                                m::mock(
                                    Crawler::class,
                                    function($mock) {
                                        /** @var m\Mock $mock */

                                        $mock
                                            ->shouldReceive('filter')
                                            ->with('image_url')
                                            ->andReturn(
                                                m::mock(
                                                    [
                                                        'text' => 'goodreads.com/123m/456.jpg'
                                                    ]
                                                )
                                            );

                                        $mock
                                            ->shouldReceive('filter')
                                            ->with('title')
                                            ->andReturn(
                                                m::mock(
                                                    [
                                                        'text' => 'title1'
                                                    ]
                                                )
                                            );

                                        $mock
                                            ->shouldReceive('filter')
                                            ->with('link')
                                            ->andReturn(
                                                m::mock(
                                                    [
                                                        'text' => 'link1'
                                                    ]
                                                )
                                            );
                                    }
                                )
                            );

                        $mock
                            ->shouldReceive('filter')
                            ->with('read_at')
                            ->andReturn(
                                m::mock(
                                    [
                                        'text' => '2016-05-15 19:37:06 EST'
                                    ]
                                )
                            );
                    }
                )
            );

        $this
            ->source
            ->shouldReceive('getImageSize')
            ->with('goodreads.com/123l/456.jpg')
            ->andReturn(
                [
                    100,
                    300
                ]
            );

        $this
            ->source
            ->shouldReceive('createCrawler')
            ->with('item2')
            ->andReturn(
                m::mock(
                    Crawler::class,
                    function($mock) {
                        /** @var m\Mock $mock */

                        $mock
                            ->shouldReceive('filter')
                            ->with('id')
                            ->andReturn(
                                m::mock(
                                    [
                                        'text' => 2
                                    ]
                                )
                            );

                        $mock
                            ->shouldReceive('filter')
                            ->with('book')
                            ->andReturn(
                                m::mock(
                                    Crawler::class,
                                    function($mock) {
                                        /** @var m\Mock $mock */

                                        $mock
                                            ->shouldReceive('filter')
                                            ->with('image_url')
                                            ->andReturn(
                                                m::mock(
                                                    [
                                                        'text' => 'goodreads.com/nophoto/123.jpg'
                                                    ]
                                                )
                                            );

                                        $mock
                                            ->shouldReceive('filter')
                                            ->with('title')
                                            ->andReturn(
                                                m::mock(
                                                    [
                                                        'text' => 'title2'
                                                    ]
                                                )
                                            );

                                        $mock
                                            ->shouldReceive('filter')
                                            ->with('link')
                                            ->andReturn(
                                                m::mock(
                                                    [
                                                        'text' => 'link2'
                                                    ]
                                                )
                                            );
                                    }
                                )
                            );

                        $mock
                            ->shouldReceive('filter')
                            ->with('read_at')
                            ->andReturn(
                                m::mock(
                                    [
                                        'text' => '2016-05-15 19:37:06 EST'
                                    ]
                                )
                            );
                    }
                )
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(2)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->with(m::mustBe([
                            'goodreads',
                            2,
                            'title2',
                            'link2',
                            '2016-05-16 00:37:06',
                            null,
                            null,
                            null,

                            'goodreads',
                            1,
                            'title1',
                            'link1',
                            '2016-05-16 00:37:06',
                            'goodreads.com/123l/456.jpg',
                            100,
                            300,
                        ]));
                })
            );

        $this->source->load($this->output, 100);
    }
}
