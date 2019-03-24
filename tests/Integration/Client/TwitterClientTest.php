<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\TwitterClient;
use GuzzleHttp\Client;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class TwitterClientTest extends MockeryTestCase
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

    public function test_getUserTweets()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'statuses/user_timeline.json',
                [
                    'headers' => [
                        'Authorization' => 'Bearer key',
                    ],
                    'query' => [
                        'contributor_details' => false,
                        'screen_name' => 1,
                    ],
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '["tweets"]',
                    ]
                )
            );

        $this->assertEquals(['tweets'], $this->twitterClient->getUserTweets(1));
    }
}