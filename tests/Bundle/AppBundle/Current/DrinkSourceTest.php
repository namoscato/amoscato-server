<?php

namespace Tests\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Current\DrinkSource;
use Mockery as m;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DrinkSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var DrinkSource */
    private $target;

    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $output;

    protected function setUp()
    {
        m::mock(
            'alias:Carbon\Carbon',
            function($mock) {
                $mock
                    ->shouldReceive('parse')
                    ->with('created at')
                    ->andReturn(
                        m::mock(
                            [
                                'toDateTimeString' => 'date'
                            ]
                        )
                    );
            }
        );

        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');

        $this->target = new DrinkSource($this->client);

        $this->target->setUsername('username');

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
                                'created_at' => 'created at',
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
                'date' => 'date',
                'name' => 'beer',
                'venue' => 'venue',
                'url' => 'url',
            ],
            $this->target->load($this->output)
        );
    }
}
