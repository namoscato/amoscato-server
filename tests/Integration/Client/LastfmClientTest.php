<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\LastfmClient;
use Amoscato\Integration\Exception\LastfmBadResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class LastfmClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var LastfmClient */
    private $flickrClient;

    protected function setUp(): void
    {
        $this->client = m::mock(Client::class);

        $this->flickrClient = new LastfmClient($this->client, 'key');
    }

    public function testGetAlbumInfoById(): void
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                '',
                [
                    'query' => [
                        'mbid' => 1,
                        'api_key' => 'key',
                        'format' => 'json',
                        'method' => 'album.getInfo',
                    ],
                ]
            )
            ->andReturn(new Response(200, [], Utils::jsonEncode(['album' => 'data'])));

        self::assertSame('data', $this->flickrClient->getAlbumInfoById(1));
    }

    public function testGetAlbumInfoByNameSuccess(): void
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                '',
                [
                    'query' => [
                        'artist' => 'foo',
                        'album' => 'bar',
                        'api_key' => 'key',
                        'format' => 'json',
                        'method' => 'album.getInfo',
                    ],
                ]
            )
            ->andReturn(new Response(200, [], Utils::jsonEncode(['album' => 'data'])));

        self::assertSame('data', $this->flickrClient->getAlbumInfoByName('foo', 'bar'));
    }

    public function testGetAlbumInfoByNameError(): void
    {
        $this
            ->client
            ->shouldReceive('get')
            ->andReturn(new Response(200, [], Utils::jsonEncode(['no_album' => 'value'])));

        self::assertEquals(
            (object) [
                'no_album' => 'value',
            ],
            $this->flickrClient->getAlbumInfoByName('foo', 'bar')
        );
    }

    public function testGetRecentTracks(): void
    {
        $this
            ->client
            ->shouldReceive('get')
            ->once()
            ->with(
                '',
                [
                    'query' => [
                        'user' => 1,
                        'api_key' => 'key',
                        'format' => 'json',
                        'method' => 'user.getRecentTracks',
                    ],
                ]
            )
            ->andReturn(new Response(
                200,
                [],
                Utils::jsonEncode([
                'recenttracks' => [
                    'track' => ['data'],
                ],
                ]))
            );

        self::assertSame(['data'], $this->flickrClient->getRecentTracks(1));
    }

    public function testGetRecentTracksException(): void
    {
        $this->expectException(LastfmBadResponseException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode(1);

        $this
            ->client
            ->shouldReceive('get')
            ->andReturn(new Response(
                200,
                [],
                Utils::jsonEncode([
                'error' => 1,
                'message' => 'foo',
                ]))
            );

        self::assertSame('data', $this->flickrClient->getRecentTracks(1));
    }
}
