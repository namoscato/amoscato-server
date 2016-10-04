<?php

namespace Tests\Bundle\AppBundle\Current\Source;

use Amoscato\Bundle\AppBundle\Current\MusicSource;
use Mockery as m;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
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
        m::mock(
            'alias:Carbon\Carbon',
            [
                'createFromTimestampUTC' => m::mock(
                    [
                        'toDateTimeString' => 'DATE'
                    ]
                )
            ]
        );

        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');

        $this->target = new MusicSource($this->client);

        $this
            ->target
            ->setUser(1);

        $this->output = m::mock('Symfony\Component\Console\Output\OutputInterface');
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
                    'limit' => 3
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
                            'uts' => 123
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
                'date' => 'DATE',
                'name' => 'NAME',
                'url' => 'URL'
            ],
            $this->target->load($this->output)
        );
    }
}
