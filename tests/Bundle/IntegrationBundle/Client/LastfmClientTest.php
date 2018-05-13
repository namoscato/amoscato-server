<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\LastfmClient;
use Mockery as m;
use GuzzleHttp\Client;

class LastfmClientTest extends \PHPUnit_Framework_TestCase
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

        $this->assertSame(
            'data',
            $this->flickrClient->getAlbumInfoById(1)
        );
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

        $this->assertSame(
            'data',
            $this->flickrClient->getAlbumInfoByName('foo', 'bar')
        );
    }

    public function test_getAlbumInfoByName_error()
    {
        $this->client
            ->shouldReceive('get')
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"error":"data"}'
                    ]
                )
            );

        $this->assertEquals(
            (object) [
                'error' => 'data'
            ],
            $this->flickrClient->getAlbumInfoByName('foo', 'bar')
        );
    }

    public function test_getRecentTracks()
    {
        $this->client
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

        $this->assertSame(
            'data',
            $this->flickrClient->getRecentTracks(1)
        );
    }
}
