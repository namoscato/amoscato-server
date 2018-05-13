<?php

namespace Tests\Bundle\AppBundle\Current\Source;

use Amoscato\Bundle\AppBundle\Current\MusicSource;
use Amoscato\Bundle\IntegrationBundle\Client\LastfmClient;
use Mockery as m;
use Symfony\Component\Console\Output\OutputInterface;

class MusicSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var MusicSource */
    private $target;

    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $output;

    protected function setUp()
    {
        $this->client = m::mock(LastfmClient::class);

        $this->target = new MusicSource($this->client, 1);

        $this->output = m::mock(OutputInterface::class);
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load()
    {
        $this
            ->client
            ->shouldReceive('getRecentTracks')
            ->with(
                1,
                [
                    'limit' => 2
                ]
            )
            ->andReturn(
                [
                    (object) [
                        'artist' => (object) [
                            '#text' => ''
                        ]
                    ],
                    (object) [
                        'album' => (object) [
                            '#text' => 'ALBUM'
                        ],
                        'artist' => (object) [
                            '#text' => 'ARTIST'
                        ],
                        'date' => (object) [
                            'uts' => 1526234751
                        ],
                        'name' => 'NAME',
                        'url' => 'URL'
                    ]
                ]
            );

        $this->assertEquals(
            [
                'album' => 'ALBUM',
                'artist' => 'ARTIST',
                'date' => '2018-05-13 18:05:51',
                'name' => 'NAME',
                'url' => 'URL'
            ],
            $this->target->load($this->output)
        );
    }
}
