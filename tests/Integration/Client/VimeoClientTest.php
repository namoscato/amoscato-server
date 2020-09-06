<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\VimeoClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class VimeoClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var VimeoClient */
    private $vimeoClient;

    protected function setUp(): void
    {
        $this->client = m::mock(Client::class);

        $this->vimeoClient = new VimeoClient($this->client, 'token');
    }

    public function test_getPublicPhotos(): void
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'me/likes',
                [
                    'headers' => [
                        'Authorization' => 'bearer token',
                    ],
                    'query' => [
                        'sort' => 'date',
                    ],
                ]
            )
            ->andReturn(new Response(200, [], \GuzzleHttp\json_encode(['videos' => 'data'])));

        self::assertEquals(
            (object) [
                'videos' => 'data',
            ],
            $this->vimeoClient->getLikes()
        );
    }
}
