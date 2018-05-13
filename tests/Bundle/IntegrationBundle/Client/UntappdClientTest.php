<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\UntappdClient;
use Mockery as m;
use GuzzleHttp\Client;

class UntappdClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var UntappdClient */
    private $untappdClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->untappdClient = new UntappdClient($this->client, 'key', 'client');
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_getBadgeUrl()
    {
        $this->assertSame(
            'https://untappd.com/user/username/badges/id',
            $this->untappdClient->getBadgeUrl('username', 'id')
        );
    }

    public function test_getCheckinUrl()
    {
        $this->assertSame(
            'https://untappd.com/user/username/checkin/id',
            $this->untappdClient->getCheckinUrl('username', 'id')
        );
    }

    public function test_getUserBadges()
    {
        $this
            ->client
            ->shouldReceive('get')
            ->with(
                'user/badges/username',
                [
                    'query' => [
                        'client_id' => 'client',
                        'client_secret' => 'key',
                        'limit' => 1
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"response":"data"}'
                    ]
                )
            );

        $this->assertSame(
            'data',
            $this->untappdClient->getUserBadges(
                'username',
                [
                    'limit' => 1
                ]
            )
        );
    }

    public function test_getUserCheckins()
    {
        $this
            ->client
            ->shouldReceive('get')
            ->with(
                'user/checkins/username',
                [
                    'query' => [
                        'client_id' => 'client',
                        'client_secret' => 'key'
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => '{"response":"data"}'
                    ]
                )
            );

        $this->assertSame(
            'data',
            $this->untappdClient->getUserCheckins('username')
        );
    }
}
