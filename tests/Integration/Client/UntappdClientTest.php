<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\UntappdClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class UntappdClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var UntappdClient */
    private $untappdClient;

    protected function setUp(): void
    {
        $this->client = m::mock(Client::class);

        $this->untappdClient = new UntappdClient($this->client, 'key', 'client');
    }

    public function testGetBadgeUrl(): void
    {
        self::assertSame(
            'https://untappd.com/user/username/badges/id',
            $this->untappdClient->getBadgeUrl('username', 'id')
        );
    }

    public function testGetCheckinUrl(): void
    {
        self::assertSame(
            'https://untappd.com/user/username/checkin/id',
            $this->untappdClient->getCheckinUrl('username', 'id')
        );
    }

    public function testGetUserBadges(): void
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
            ->andReturn(new Response(200, [], Utils::jsonEncode(['response' => 'data'])));

        self::assertSame(
            'data',
            $this->untappdClient->getUserBadges(
                'username',
                [
                    'limit' => 1,
                ]
            )
        );
    }

    public function testGetUserCheckins(): void
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
            ->andReturn(new Response(200, [], Utils::jsonEncode(['response' => 'data'])));

        self::assertSame(
            'data',
            $this->untappdClient->getUserCheckins('username')
        );
    }
}
