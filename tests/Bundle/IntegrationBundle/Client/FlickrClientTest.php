<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\FlickrClient;
use Mockery as m;

class FlickrClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var FlickrClient */
    private $flickrClient;

    protected function setUp()
    {
        $this->client = m::mock('GuzzleHttp\Client');

        $this->flickrClient = new FlickrClient($this->client, 'key');
    }

    public function test_getPublicPhotos()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                '',
                [
                    'query' => [
                        'user_id' => 1,
                        'api_key' => 'key',
                        'format' => 'json',
                        'method' => 'flickr.people.getPublicPhotos',
                        'nojsoncallback' => 1
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"photos":{"photo":"public photos"}}'
                    ]
                )
            );

        $this->assertSame(
            'public photos',
            $this->flickrClient->getPublicPhotos(1)
        );
    }
}
