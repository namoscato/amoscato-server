<?php

declare(strict_types=1);

namespace Tests\Source\Current;

use Amoscato\Integration\Client\StravaClient;
use Amoscato\Source\Current\AthleticActivitySource;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AthleticActivitySourceTest extends MockeryTestCase
{
    /** @var AthleticActivitySource */
    private $target;

    /** @var m\Mock */
    private $stravaClient;

    /** @var OutputInterface */
    private $output;

    protected function setUp(): void
    {
        $this->stravaClient = m::mock(StravaClient::class);

        $this->target = new AthleticActivitySource(
            $this->stravaClient,
            'strava.com/'
        );

        $this->output = new NullOutput();
    }

    public function test_load(): void
    {
        $this
            ->stravaClient
            ->shouldReceive('getActivities')
            ->with(['per_page' => 1])
            ->andReturn([
                (object) [
                    'start_date' => '2018-05-22 12:00:00Z',
                    'distance' => 10000,
                    'moving_time' => 60,
                    'type' => 'Run',
                    'id' => 123,
                ],
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
