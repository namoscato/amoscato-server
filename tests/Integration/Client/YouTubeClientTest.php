<?php

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\YouTubeClient;
use GuzzleHttp\Client;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class YouTubeClientTest extends TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var YouTubeClient */
    private $youtubeClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->youtubeClient = new YouTubeClient($this->client, 'key');
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
                'playlistItems',
                [
                    'query' => [
                        'key' => 'key',
                        'part' => 'snippet',
                        'playlistId' => 1
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"key":"value"}'
                    ]
                )
            );

        $this->assertEquals(
            (object) [
                'key' => 'value'
            ],
            $this->youtubeClient->getPlaylistItems(1)
        );
    }
}
