<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\FlickrClient;
use GuzzleHttp\Client;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;

class FlickrClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var FlickrClient */
    private $flickrClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

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
                        'nojsoncallback' => 1,
                    ],
                ]
            )
            ->andReturn(
                m::mock(
                    ResponseInterface::class,
                    [
                        'getBody' => '{"photos":{"photo":["public photos"]}}',
                    ]
                )
            );

        $this->assertSame(
            ['public photos'],
            $this->flickrClient->getPublicPhotos(1)
        );
    }
}
