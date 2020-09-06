<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\TwitterClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class TwitterClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var TwitterClient */
    private $twitterClient;

    protected function setUp(): void
    {
        $this->client = m::mock(Client::class);

        $this->twitterClient = new TwitterClient($this->client, 'key');
    }

    public function test_getUserTweets(): void
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
            ->andReturn(new Response(200, [], \GuzzleHttp\json_encode(['tweets'])));

        self::assertEquals(['tweets'], $this->twitterClient->getUserTweets(1));
    }
}
