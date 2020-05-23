<?php

declare(strict_types=1);

namespace Tests\Source\Current;

use Amoscato\Integration\Client\LastfmClient;
use Amoscato\Source\Current\MusicSource;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class MusicSourceTest extends MockeryTestCase
{
    /** @var MusicSource */
    private $target;

    /** @var m\Mock */
    private $client;

    /** @var OutputInterface */
    private $output;

    protected function setUp(): void
    {
        $this->client = m::mock(LastfmClient::class);

        $this->target = new MusicSource($this->client, 1);

        $this->output = new NullOutput();
    }

    public function test_load(): void
    {
        $this
            ->client
            ->shouldReceive('getRecentTracks')
            ->with(
                1,
                [
                    'limit' => 2,
                ]
            )
            ->andReturn(
                [
                    (object) [
                        'artist' => (object) [
                            '#text' => '',
                        ],
                    ],
                    (object) [
                        'album' => (object) [
                            '#text' => 'ALBUM',
                        ],
                        'artist' => (object) [
                            '#text' => 'ARTIST',
                        ],
                        'date' => (object) [
                            'uts' => 1526234751,
                        ],
                        'name' => 'NAME',
                        'url' => 'URL',
                    ],
                ]
            );

        $this->assertEquals(
            [
                'album' => 'ALBUM',
                'artist' => 'ARTIST',
                'date' => '2018-05-13 18:05:51',
                'name' => 'NAME',
                'url' => 'URL',
            ],
            $this->target->load($this->output)
        );
    }
}
