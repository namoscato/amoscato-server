<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\YouTubeClient;
use Mockery as m;

class YouTubeClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var YouTubeClient */
    private $youtubeClient;

    protected function setUp()
    {
        $this->client = m::mock('GuzzleHttp\Client');

        $this->youtubeClient = new YouTubeClient($this->client, 'key');
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
