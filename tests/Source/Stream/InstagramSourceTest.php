<?php

declare(strict_types=1);

namespace Tests\Source\Stream;

use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\InstagramClient;
use Amoscato\Source\Stream\InstagramSource;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use Carbon\Carbon;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use PDOStatement;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class InstagramSourceTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var InstagramSource */
    private $source;

    /** @var OutputInterface */
    private $output;

    protected function setUp(): void
    {
        $this->client = m::mock(InstagramClient::class);

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider]', InstagramSource::class),
            [
                m::mock(PDOFactory::class),
                m::mock(FtpClient::class),
                $this->client,
            ],
            [
                'getStreamStatementProvider' => $this->statementProvider,
            ]
        );

        $this->output = new NullOutput();
    }

    public function testLoad(): void
    {
        $this
            ->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('instagram')
            ->andReturn(m::mock(
                PDOStatement::class,
                [
                    'execute' => null,
                    'fetch' => ['source_id' => '10'],
                ]
            ));

        $this->client
            ->shouldReceive('getMostRecentMedia')
            ->andReturn((object) [
                'data' => [
                    (object) [
                        'id' => '1',
                        'caption' => (object) ['text' => 'CAPTION'],
                        'location' => (object) ['name' => 'LOCATION'],
                        'images' => (object) [
                            'low_resolution' => (object) [
                                'url' => 'img1.jpg',
                                'width' => 'w1',
                                'height' => 'h1',
                            ],
                        ],
                        'link' => 'instagram.com/1',
                        'created_time' => Carbon::create(2019, 3, 24, 12, 0, 0, 'UTC')->timestamp,
                    ],
                    (object) [
                        'id' => '2',
                        'location' => (object) ['name' => 'LOCATION'],
                        'images' => (object) [
                            'low_resolution' => (object) [
                                'url' => 'img2.jpg',
                                'width' => 'w',
                                'height' => 'h',
                            ],
                        ],
                        'carousel_media' => [
                            (object) [
                                'images' => (object) [
                                    'low_resolution' => (object) [
                                        'url' => 'img3.jpg',
                                        'width' => 'w3',
                                        'height' => 'h3',
                                    ],
                                ],
                            ],
                            (object) [
                                'images' => (object) [
                                    'low_resolution' => (object) [
                                        'url' => 'img4.jpg',
                                        'width' => 'w4',
                                        'height' => 'h4',
                                    ],
                                ],
                            ],
                        ],
                        'link' => 'instagram.com/2',
                        'created_time' => Carbon::create(2019, 3, 24, 12, 0, 0, 'UTC')->timestamp,
                    ],
                ],
            ]);

        $this
            ->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(3)
            ->andReturn(m::mock(
                PDOStatement::class,
                function ($mock) {
                    /* @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->with([
                            'instagram',
                            '2_1',
                            'LOCATION',
                            'instagram.com/2',
                            '2019-03-24 12:00:00',
                            'img4.jpg',
                            'w4',
                            'h4',

                            'instagram',
                            '2',
                            'LOCATION',
                            'instagram.com/2',
                            '2019-03-24 12:00:00',
                            'img3.jpg',
                            'w3',
                            'h3',

                            'instagram',
                            '1',
                            'CAPTION',
                            'instagram.com/1',
                            '2019-03-24 12:00:00',
                            'img1.jpg',
                            'w1',
                            'h1',
                        ]);
                }
            ));

        $this->source->load($this->output);
    }
}
