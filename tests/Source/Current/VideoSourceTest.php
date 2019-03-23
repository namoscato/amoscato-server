<?php

declare(strict_types=1);

namespace Tests\Source\Current;

use Amoscato\Integration\Client\VimeoClient;
use Amoscato\Integration\Client\YouTubeClient;
use Amoscato\Source\Current\VideoSource;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class VideoSourceTest extends MockeryTestCase
{
    /** @var VideoSource */
    private $target;

    /** @var m\Mock */
    private $youTubeClient;

    /** @var m\Mock */
    private $vimeoClient;

    /** @var OutputInterface */
    private $output;

    protected function setUp(): void
    {
        $this->youTubeClient = m::mock(YouTubeClient::class);

        $this->vimeoClient = m::mock(VimeoClient::class);

        $this->target = new VideoSource(
            $this->youTubeClient,
            'ID',
            'youtube.com/',
            $this->vimeoClient
        );

        $this->output = new NullOutput();
    }

    /**
     * @dataProvider getLoadTests
     * @param string $youTubeDate
     * @param string $vimeoDate
     * @param array $expected
     */
    public function testLoad(string $youTubeDate, string $vimeoDate, array $expected): void
    {
        $this
            ->youTubeClient
            ->shouldReceive('getPlaylistItems')
            ->with(
                'ID',
                [
                    'maxResults' => 1,
                ]
            )
            ->andReturn(
                (object) [
                    'items' => [
                        (object) [
                            'snippet' => (object) [
                                'publishedAt' => $youTubeDate,
                                'title' => 'yt title',
                                'resourceId' => (object) [
                                    'videoId' => 'v1',
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this
            ->vimeoClient
            ->shouldReceive('getLikes')
            ->with(
                [
                    'per_page' => 1,
                ]
            )
            ->andReturn(
                (object) [
                    'data' => [
                        (object) [
                            'metadata' => (object) [
                                'interactions' => (object) [
                                    'like' => (object) [
                                        'added_time' => $vimeoDate,
                                    ],
                                ],
                            ],
                            'name' => 'v name',
                            'link' => 'v link',
                        ],
                    ],
                ]
            );

        $this->assertSame($expected, $this->target->load($this->output));
    }

    public function getLoadTests(): array
    {
        return [
            [
                '2019-03-23 12:00:00',
                '2018-03-23 12:00:00',
                [
                    'date' => '2019-03-23 12:00:00',
                    'title' => 'yt title',
                    'url' => 'youtube.com/v1',
                ],
            ],
            [
                '2018-03-23 12:00:00',
                '2019-03-23 12:00:00',
                [
                    'date' => '2019-03-23 12:00:00',
                    'title' => 'v name',
                    'url' => 'v link',
                ],
            ],
        ];
    }
}
