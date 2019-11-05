<?php

declare(strict_types=1);

namespace Tests\Source\Current;

use Amoscato\Integration\Client\UntappdClient;
use Amoscato\Source\Current\DrinkSource;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DrinkSourceTest extends MockeryTestCase
{
    /** @var DrinkSource */
    private $target;

    /** @var m\Mock */
    private $client;

    /** @var OutputInterface */
    private $output;

    protected function setUp()
    {
        $this->client = m::mock(UntappdClient::class);

        $this->target = new DrinkSource($this->client, 'username');

        $this->output = new NullOutput();
    }

    public function test_load()
    {
        $this
            ->client
            ->shouldReceive('getUserCheckins')
            ->with(
                'username',
                [
                    'limit' => 1,
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
                                    'brewery_name' => 'brewery',
                                ],
                                'beer' => (object) [
                                    'beer_name' => 'beer',
                                ],
                                'venue' => (object) [
                                    'venue_name' => 'venue',
                                ],
                            ],
                        ],
                    ],
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
