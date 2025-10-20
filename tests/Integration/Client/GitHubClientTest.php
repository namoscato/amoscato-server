<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\GitHubClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
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

    public function testGetUserEvents(): void
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'users/testuser/events',
                [
                    'auth' => ['id', 'secret'],
                    'query' => [],
                ]
            )
            ->andReturn(new Response(200, [], Utils::jsonEncode(['event1', 'event2'])));

        self::assertEquals(['event1', 'event2'], $this->gitHubClient->getUserEvents('testuser'));
    }

    public function testGetUserEventsWithArgs(): void
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'users/testuser/events',
                [
                    'auth' => ['id', 'secret'],
                    'query' => ['page' => 2],
                ]
            )
            ->andReturn(new Response(200, [], Utils::jsonEncode(['event3'])));

        self::assertEquals(['event3'], $this->gitHubClient->getUserEvents('testuser', ['page' => 2]));
    }

    public function testCompareCommits(): void
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'repos/owner/repo/compare/abc123...def456',
                [
                    'auth' => ['id', 'secret'],
                    'query' => [],
                ]
            )
            ->andReturn(new Response(200, [], Utils::jsonEncode(['commits' => ['commit1', 'commit2']])));

        $result = $this->gitHubClient->compareCommits('owner', 'repo', 'abc123...def456');

        self::assertEquals(
            (object) [
                'commits' => ['commit1', 'commit2'],
            ],
            $result
        );
    }
}
