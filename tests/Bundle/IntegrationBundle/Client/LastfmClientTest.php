<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\LastfmClient;
use Mockery as m;

class LastfmClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var LastfmClient */
    private $flickrClient;

    protected function setUp()
    {
        $this->client = m::mock('GuzzleHttp\Client');

        $this->flickrClient = new LastfmClient($this->client, 'key');
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

    public function test_getAlbumInfoByName()
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
