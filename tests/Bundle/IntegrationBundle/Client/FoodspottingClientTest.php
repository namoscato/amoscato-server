<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\FoodspottingClient;
use Mockery as m;

class FoodspottingClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var FoodspottingClient */
    private $flickrClient;

    protected function setUp()
    {
        $this->client = m::mock('GuzzleHttp\Client');

        $this->flickrClient = new FoodspottingClient($this->client, 'key');
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_getReviews()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'people/1/reviews.json',
                [
                    'query' => [
                        'api_key' => 'key'
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"data":{"reviews":"data"}}'
                    ]
                )
            );

        $this->assertSame(
            'data',
            $this->flickrClient->getReviews(1)
        );
    }
}
