<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\GitHubClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class GitHubClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var GitHubClient */
    private $gitHubClient;

    protected function setUp(): void
    {
        $this->client = m::mock(Client::class);

        $this->gitHubClient = new GitHubClient($this->client, 'secret', 'id');
    }

    public function test_getUserEvents(): void
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'users/1/events',
                [
                    'auth' => ['id', 'secret'],
                    'query' => [],
                ]
            )
            ->andReturn(new Response(200, [], \GuzzleHttp\json_encode(['data'])));


        $this->assertEquals(['data'], $this->gitHubClient->getUserEvents(1));
    }

    public function test_getCommit(): void
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'github.com',
                m::type('array')
            )
            ->andReturn(new Response(200, [], \GuzzleHttp\json_encode(['key' => 'data'])));

        $this->assertEquals(
            (object) [
                'key' => 'data',
            ],
            $this->gitHubClient->getCommit('github.com')
        );
    }
}
