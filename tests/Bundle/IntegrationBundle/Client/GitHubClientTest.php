<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\GitHubClient;
use Mockery as m;

class GitHubClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var GitHubClient */
    private $gitHubClient;

    protected function setUp()
    {
        $this->client = m::mock('GuzzleHttp\Client');

        $this->gitHubClient = new GitHubClient($this->client, 'secret');
        $this->gitHubClient->setClientId('id');
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
