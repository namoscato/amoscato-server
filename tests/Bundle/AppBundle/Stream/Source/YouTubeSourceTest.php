<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Source\YouTubeSource;
use Amoscato\Bundle\IntegrationBundle\Client\YouTubeClient;
use Mockery as m;
use Amoscato\Database\PDOFactory;
use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Symfony\Component\Console\Output\OutputInterface;

class YouTubeSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var YouTubeSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock(YouTubeClient::class);
        
        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider]', YouTubeSource::class),
            [
                m::mock(PDOFactory::class),
                m::mock(FtpClient::class),
                $this->client,
                10,
                'youtube.com/'
            ]
        );

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            OutputInterface::class,
            [
                'writeDebug' => null,
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

        $this
            ->client
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
                                'publishedAt' => '2018-05-13 12:00:00',
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
            );

        $this
            ->client
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
                            '2018-05-13 12:00:00',
                            'img.jpg',
                            100,
                            300,
                        ]));
                })
            );

        $this->source->load($this->output);
    }
}
