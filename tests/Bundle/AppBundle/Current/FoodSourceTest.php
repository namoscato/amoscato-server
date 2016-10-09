<?php

namespace Tests\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Current\FoodSource;
use Mockery as m;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class FoodSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var FoodSource */
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

        $this->target = new FoodSource($this->client);

        $this->target->setPersonId(1);
        $this->target->setReviewUri('foodspotting.com/');

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
            ->shouldReceive('getReviews')
            ->with(
                1,
                [
                    'per_page' => 1,
                    'sort' => 'latest'
                ]
            )
            ->andReturn(
                [
                    (object) [
                        'id' => 2,
                        'created_at' => 'created at',
                        'item' => (object) [
                            'name' => 'item'
                        ],
                        'place' => (object) [
                            'name' => 'place'
                        ]
                    ]
                ]
            );

        $this->assertSame(
            [
                'item' => 'item',
                'place' => 'place',
                'date' => 'date',
                'url' => 'foodspotting.com/2'
            ],
            $this->target->load($this->output)
        );
    }
}
