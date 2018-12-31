<?php

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\GitHubClient;
use GuzzleHttp\Client;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class GitHubClientTest extends TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var GitHubClient */
    private $gitHubClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->gitHubClient = new GitHubClient($this->client, 'secret', 'id');
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_getUserEvents()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'users/1/events',
                [
                    'query' => [
                        'client_id' => 'id',
                        'client_secret' => 'secret'
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"key":"data"}'
                    ]
                )
            );

        $this->assertEquals(
            (object) [
                'key' => 'data'
            ],
            $this->gitHubClient->getUserEvents(1)
        );
    }

    public function test_getCommit()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'github.com',
                [
                    'query' => [
                        'client_id' => 'id',
                        'client_secret' => 'secret'
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"key":"data"}'
                    ]
                )
            );

        $this->assertEquals(
            (object) [
                'key' => 'data'
            ],
            $this->gitHubClient->getCommit('github.com')
        );
    }
}
