<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\VimeoClient;
use Mockery as m;

class VimeoClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var VimeoClient */
    private $vimeoClient;

    protected function setUp()
    {
        $this->client = m::mock('GuzzleHttp\Client');

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
