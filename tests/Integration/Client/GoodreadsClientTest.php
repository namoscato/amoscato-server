<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\GoodreadsClient;
use GuzzleHttp\Client;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\DomCrawler\Crawler;

class GoodreadsClientTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var GoodreadsClient */
    private $goodreadsClient;

    protected function setUp()
    {
        $this->client = m::mock(Client::class);

        $this->goodreadsClient = m::mock(
            sprintf('%s[createCrawler]', GoodreadsClient::class),
            [
                $this->client,
                'key',
            ]
        );
    }

    public function test_getCurrentlyReadingBooks()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'review/list/1.xml',
                [
                    'query' => [
                        'key' => 'key',
                        'v' => 2,
                        'shelf' => 'currently-reading',
                    ],
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => 'body',
                    ]
                )
            );

        $this->goodreadsClient
            ->shouldReceive('createCrawler')
            ->with('body')
            ->andReturn(
                m::mock(
                    Crawler::class,
                    function ($mock) {
                        /* @var m\Mock $mock */

                        $mock
                            ->shouldReceive('filter')
                            ->with('GoodreadsResponse reviews review')
                            ->andReturn(['books']);
                    }
                )
            );

        $this->assertSame(
            ['books'],
            $this->goodreadsClient->getCurrentlyReadingBooks(1)
        );
    }

    public function test_getReadBooks()
    {
        $this->client
            ->shouldReceive('get')
            ->once()
            ->with(
                'review/list/1.xml',
                [
                    'query' => [
                        'key' => 'key',
                        'v' => 2,
                        'shelf' => 'read',
                    ],
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => 'body',
                    ]
                )
            );

        $this->goodreadsClient
            ->shouldReceive('createCrawler')
            ->with('body')
            ->andReturn(
                m::mock(
                    Crawler::class,
                    function ($mock) {
                        /* @var m\Mock $mock */

                        $mock
                            ->shouldReceive('filter')
                            ->with('GoodreadsResponse reviews review')
                            ->andReturn(['books']);
                    }
                )
            );

        $this->assertSame(
            ['books'],
            $this->goodreadsClient->getReadBooks(1)
        );
    }
}