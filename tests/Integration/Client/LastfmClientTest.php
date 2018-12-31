<?php

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\LastfmClient;
use GuzzleHttp\Client;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class LastfmClientTest extends TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var LastfmClient */
    private $flickrClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->flickrClient = new LastfmClient($this->client, 'key');
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_getAlbumInfoById()
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
                        'method' => 'album.getInfo'
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"album":"data"}'
                    ]
                )
            );

        $this->assertSame('data', $this->flickrClient->getAlbumInfoById(1));
    }

    public function test_getAlbumInfoByName_success()
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
                        'method' => 'album.getInfo'
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"album":"data"}'
                    ]
                )
            );

        $this->assertSame('data', $this->flickrClient->getAlbumInfoByName('foo', 'bar'));
    }

    public function test_getAlbumInfoByName_error()
    {
        $this
            ->client
            ->shouldReceive('get')
            ->andReturn(m::mock(['getBody' => '{"no_album":"value"}']));

        $this->assertEquals(
            (object) [
                'no_album' => 'value'
            ],
            $this->flickrClient->getAlbumInfoByName('foo', 'bar')
        );
    }

    public function test_getRecentTracks()
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
                        'method' => 'user.getRecentTracks'
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"recenttracks":{"track":"data"}}'
                    ]
                )
            );

        $this->assertSame('data', $this->flickrClient->getRecentTracks(1));
    }

    /**
     * @expectedException \Amoscato\Integration\Exception\LastfmBadResponseException
     * @expectedExceptionMessage foo
     * @expectedExceptionCode 1
     */
    public function test_getRecentTracks_exception()
    {
        $this
            ->client
            ->shouldReceive('get')
            ->andReturn(m::mock([
                'getBody' => \GuzzleHttp\json_encode([
                    'error' => 1,
                    'message' => 'foo',
                ])
            ]));

        $this->assertSame('data', $this->flickrClient->getRecentTracks(1));
    }
}
