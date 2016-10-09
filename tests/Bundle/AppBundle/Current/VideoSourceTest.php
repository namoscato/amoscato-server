<?php

namespace Tests\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Current\VideoSource;
use Mockery as m;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class VideoSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var VideoSource */
    private $target;

    /** @var  m\Mock */
    private $youTubeClient;

    /** @var  m\Mock */
    private $vimeoClient;

    /** @var  m\Mock */
    private $output;

    /** @var  m\Mock */
    private $carbonParseYouTube;

    /** @var  m\Mock */
    private $carbonParseVimeo;

    protected function setUp()
    {
        $this->carbonParseYouTube = m::mock(
            [
                'toDateTimeString' => 'yt date string'
            ]
        );

        $this->carbonParseVimeo = m::mock(
            [
                'toDateTimeString' => 'v date string'
            ]
        );

        m::mock(
            'alias:Carbon\Carbon',
            function($mock) {
                $mock
                    ->shouldReceive('parse')
                    ->with('yt date')
                    ->andReturn($this->carbonParseYouTube);

                $mock
                    ->shouldReceive('parse')
                    ->with('v date')
                    ->andReturn($this->carbonParseVimeo);
            }
        );

        $this->youTubeClient = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');

        $this->vimeoClient = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');

        $this->target = new VideoSource($this->youTubeClient, $this->vimeoClient);

        $this->target->setYouTubePlaylistId('ID');
        $this->target->setYouTubeVideoUri('youtube.com/');

        $this->output = m::mock('Symfony\Component\Console\Output\OutputInterface');

        $this
            ->youTubeClient
            ->shouldReceive('getPlaylistItems')
            ->with(
                'ID',
                [
                    'maxResults' => 1
                ]
            )
            ->andReturn(
                (object) [
                    'items' => [
                        (object) [
                            'snippet' => (object) [
                                'publishedAt' => 'yt date',
                                'title' => 'yt title',
                                'resourceId' => (object) [
                                    'videoId' => 'v1'
                                ]
                            ]
                        ]
                    ]
                ]
            );

        $this
            ->vimeoClient
            ->shouldReceive('getLikes')
            ->with(
                [
                    'per_page' => 1
                ]
            )
            ->andReturn(
                (object) [
                    'data' => [
                        (object) [
                            'metadata' => (object) [
                                'interactions' => (object) [
                                    'like' => (object) [
                                        'added_time' => 'v date'
                                    ]
                                ]
                            ],
                            'name' => 'v name',
                            'link' => 'v link'
                        ]
                    ]
                ]
            );
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load_youtube()
    {
        $this
            ->carbonParseYouTube
            ->shouldReceive('gt')
            ->with($this->carbonParseVimeo)
            ->andReturn(true);

        $this->assertSame(
            [
                'date' => 'yt date string',
                'title' => 'yt title',
                'url' => 'youtube.com/v1'
            ],
            $this->target->load($this->output)
        );
    }

    public function test_load_vimeo()
    {
        $this
            ->carbonParseYouTube
            ->shouldReceive('gt')
            ->with($this->carbonParseVimeo)
            ->andReturn(false);

        $this->assertSame(
            [
                'date' => 'v date string',
                'title' => 'v name',
                'url' => 'v link'
            ],
            $this->target->load($this->output)
        );
    }
}
