<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\YouTubeClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class YouTubeClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var YouTubeClient */
    private $youtubeClient;

    protected function setUp(): void
    {
        $this->client = m::mock(Client::class);

        $this->youtubeClient = new YouTubeClient($this->client, 'key');
    }

    public function testGetPublicPhotos(): void
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
                        'playlistId' => 1,
                    ],
                ]
            )
            ->andReturn(new Response(200, [], Utils::jsonEncode(['key' => 'value'])));

        self::assertEquals(
            (object) [
                'key' => 'value',
            ],
            $this->youtubeClient->getPlaylistItems(1)
        );
    }
}
