<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\UntappdClient;
use GuzzleHttp\Client;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class UntappdClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var UntappdClient */
    private $untappdClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->untappdClient = new UntappdClient($this->client, 'key', 'client');
    }

    public function test_getBadgeUrl()
    {
        $this->assertSame(
            'https://untappd.com/user/username/badges/id',
            $this->untappdClient->getBadgeUrl('username', 'id')
        );
    }

    public function test_getCheckinUrl()
    {
        $this->assertSame(
            'https://untappd.com/user/username/checkin/id',
            $this->untappdClient->getCheckinUrl('username', 'id')
        );
    }

    public function test_getUserBadges()
    {
        $this
            ->client
            ->shouldReceive('get')
            ->with(
                'user/badges/username',
                [
                    'query' => [
                        'client_id' => 'client',
                        'client_secret' => 'key',
                        'limit' => 1,
                    ],
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"response":"data"}',
                    ]
                )
            );

        $this->assertSame(
            'data',
            $this->untappdClient->getUserBadges(
                'username',
                [
                    'limit' => 1,
                ]
            )
        );
    }

    public function test_getUserCheckins()
    {
        $this
            ->client
            ->shouldReceive('get')
            ->with(
                'user/checkins/username',
                [
                    'query' => [
                        'client_id' => 'client',
                        'client_secret' => 'key',
                    ],
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"response":"data"}',
                    ]
                )
            );

        $this->assertSame(
            'data',
            $this->untappdClient->getUserCheckins('username')
        );
    }
}
