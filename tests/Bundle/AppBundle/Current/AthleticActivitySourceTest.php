<?php

namespace Tests\Bundle\AppBundle\Current\Source;

use Amoscato\Bundle\AppBundle\Current\AthleticActivitySource;
use Amoscato\Bundle\IntegrationBundle\Client\StravaClient;
use Amoscato\Console\Output\ConsoleOutput;
use Mockery as m;

class AthleticActivitySourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var AthleticActivitySource */
    private $target;

    /** @var m\Mock */
    private $stravaClient;

    /** @var m\Mock */
    private $output;

    protected function setUp()
    {
        $this->stravaClient = m::mock(StravaClient::class);

        $this->target = new AthleticActivitySource(
            $this->stravaClient,
            'strava.com/'
        );

        $this->output = m::mock(ConsoleOutput::class);
    }

    public function test_load()
    {
        $this
            ->stravaClient
            ->shouldReceive('getActivities')
            ->with(['per_page' => 1])
            ->andReturn([
                (object)[
                    'start_date' => '2018-05-22 12:00:00Z',
                    'distance' => 10000,
                    'moving_time' => 60,
                    'type' => 'Run',
                    'id' => 123,
                ]
            ]);

        $this->assertEquals(
            [
                'date' => '2018-05-22 12:00:00',
                'miles' => 6.21371,
                'minutes' => 1,
                'type' => 'Run',
                'url' => 'strava.com/123',
            ],
            $this->target->load($this->output)
        );
    }
}
