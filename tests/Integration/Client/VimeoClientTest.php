<?php

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\VimeoClient;
use GuzzleHttp\Client;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class VimeoClientTest extends TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var VimeoClient */
    private $vimeoClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->vimeoClient = new VimeoClient($this->client, 'token');
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_getPublicPhotos()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'me/likes',
                [
                    'headers' => [
                        'Authorization' => 'bearer token'
                    ],
                    'query' => [
                        'sort' => 'date'
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"videos":"data"}'
                    ]
                )
            );

        $this->assertEquals(
            (object) [
                'videos' => 'data'
            ],
            $this->vimeoClient->getLikes()
        );
    }
}
