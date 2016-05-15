<?php

namespace Tests\Bundle\AppBundle\Stream\Stream;

use Mockery as m;

class GoodreadsSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\GoodreadsSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');
        
        $this->source = m::mock(
            'Amoscato\Bundle\AppBundle\Stream\Source\GoodreadsSource[getPhotoStatementProvider,createCrawler,getImageSize]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                $this->client
            ]
        );

        $this->source->setUserId(10);

        $this->statementProvider = m::mock('Amoscato\Bundle\AppBundle\Stream\Query\PhotoStatementProvider');

        $this->source
            ->shouldReceive('getPhotoStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            'Symfony\Component\Console\Output\OutputInterface',
            [
                'writeln' => null,
                'writeVerbose' => null
            ]
        );
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load()
    {
        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('bindValue')
                        ->once()
                        ->with(
                            ':type',
                            'goodreads'
                        );

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

        $this->client
            ->shouldReceive('getReadBooks')
            ->with(
                10,
                [
                    'page' => 1,
                    'per_page' => 100
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

        $this->source
            ->shouldReceive('createCrawler')
            ->with('item1')
            ->andReturn(
                m::mock(
                    'Symfony\Component\DomCrawler\Crawler',
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
                                    'Symfony\Component\DomCrawler\Crawler',
                                    function($mock) {
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

        $this->source
            ->shouldReceive('getImageSize')
            ->with('goodreads.com/123l/456.jpg')
            ->andReturn(
                [
                    100,
                    300
                ]
            );

        $this->source
            ->shouldReceive('createCrawler')
            ->with('item2')
            ->andReturn(
                m::mock(
                    'Symfony\Component\DomCrawler\Crawler',
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
                                    'Symfony\Component\DomCrawler\Crawler',
                                    function($mock) {
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
            ->with(2)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
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

        $this->source->load($this->output);
    }
}
