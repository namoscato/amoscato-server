<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\TwitterClient;
use GuzzleHttp\Client;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TwitterClientTest extends TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var TwitterClient */
    private $twitterClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->twitterClient = new TwitterClient($this->client, 'key');
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_getUserTweets()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'statuses/user_timeline.json',
                [
                    'headers' => [
                        'Authorization' => "Bearer key"
                    ],
                    'query' => [
                        'contributor_details' => false,
                        'screen_name' => 1
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
            $this->twitterClient->getUserTweets(1)
        );
    }
}
