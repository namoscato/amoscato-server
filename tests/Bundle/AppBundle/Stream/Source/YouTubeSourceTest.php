<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Mockery as m;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class YouTubeSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\YouTubeSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        m::mock(
            'alias:Carbon\Carbon',
            [
                'parse->toDateTimeString' => 'date'
            ]
        );

        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');
        
        $this->source = m::mock(
            'Amoscato\Bundle\AppBundle\Stream\Source\YouTubeSource[getStreamStatementProvider]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                m::mock('\Amoscato\Bundle\AppBundle\Ftp\FtpClient'),
                $this->client
            ]
        );

        $this->source->setPlaylistId(10);
        $this->source->setVideoUri('youtube.com/');

        $this->statementProvider = m::mock('Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider');

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            'Symfony\Component\Console\Output\OutputInterface',
            [
                'writeDebug' => null,
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
            ->with('youtube')
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

        $this->client
            ->shouldReceive('getPlaylistItems')
            ->with(
                10,
                [
                    'maxResults' => 50,
                    'pageToken' => null
                ]
            )
            ->andReturn(
                (object) [
                    'nextPageToken' => 'next1',
                    'items' => [
                        (object) [
                            'snippet' => (object) [
                                'publishedAt' => 'date',
                                'title' => 'video title',
                                'thumbnails' => (object) [
                                    'medium' => (object) [
                                        'url' => 'img.jpg',
                                        'width' => 100,
                                        'height' => 300
                                    ]
                                ],
                                'resourceId' => (object) [
                                    'videoId' => 123
                                ]
                            ]
                        ],
                        (object) [
                            'snippet' => (object) [
                                'title' => 'video title',
                                'resourceId' => (object) [
                                    'videoId' => 123
                                ]
                            ]
                        ]
                    ]
                ]
            )
            ->shouldReceive('getPlaylistItems')
            ->once()
            ->with(
                10,
                [
                    'maxResults' => 50,
                    'pageToken' => 'next1'
                ]
            )
            ->andReturn(
                (object) [
                    'nextPageToken' => 'next2',
                    'items' => []
                ]
            );

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
                            'youtube',
                            123,
                            'video title',
                            'youtube.com/123',
                            'date',
                            'img.jpg',
                            100,
                            300,
                        ]));
                })
            );

        $this->source->load($this->output);
    }
}