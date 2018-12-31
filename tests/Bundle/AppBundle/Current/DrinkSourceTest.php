<?php

namespace Tests\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Current\DrinkSource;
use Amoscato\Bundle\IntegrationBundle\Client\UntappdClient;
use Amoscato\Console\Output\ConsoleOutput;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DrinkSourceTest extends TestCase
{
    /** @var DrinkSource */
    private $target;

    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $output;

    protected function setUp()
    {
        $this->client = m::mock(UntappdClient::class);

        $this->target = new DrinkSource($this->client, 'username');

        $this->output = m::mock(ConsoleOutput::class);
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load()
    {
        $this
            ->client
            ->shouldReceive('getUserCheckins')
            ->with(
                'username',
                [
                    'limit' => 1
                ]
            )
            ->andReturn(
                (object) [
                    'checkins' => (object) [
                        'items' => [
                            (object) [
                                'checkin_id' => 'id',
                                'created_at' => '2018-05-13 12:00:00',
                                'brewery' => (object) [
                                    'brewery_name' => 'brewery'
                                ],
                                'beer' => (object) [
                                    'beer_name' => 'beer'
                                ],
                                'venue' => (object) [
                                    'venue_name' => 'venue'
                                ]
                            ]
                        ]
                    ]
                ]
            );

        $this
            ->client
            ->shouldReceive('getCheckinUrl')
            ->with(
                'username',
                'id'
            )
            ->andReturn('url');

        $this->assertSame(
            [
                'brewery' => 'brewery',
                'date' => '2018-05-13 12:00:00',
                'name' => 'beer',
                'venue' => 'venue',
                'url' => 'url',
            ],
            $this->target->load($this->output)
        );
    }
}
