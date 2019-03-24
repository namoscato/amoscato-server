<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\StravaClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class StravaClientTest extends MockeryTestCase
{
    /** @var StravaClient */
    private $target;

    /** @var m\Mock */
    private $client;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->target = new StravaClient(
            $this->client,
            'TOKEN'
        );
    }

    public function test_getActivities()
    {
        $this
            ->client
            ->shouldReceive('get')
            ->with(
                'athlete/activities',
                [
                    'headers' => ['Authorization' => 'Bearer TOKEN'],
                    'query' => ['page' => 1],
                ]
            )
            ->andReturn(new Response(
                200,
                [],
                \GuzzleHttp\json_encode(['foo' => 'bar'])
            ));

        $this->assertEquals(
            (object) ['foo' => 'bar'],
            $this->target->getActivities(['page' => 1])
        );
    }
}
