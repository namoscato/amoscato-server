<?php

namespace Tests\Bundle\IntegrationBundle\Client;

use Amoscato\Bundle\IntegrationBundle\Client\GoodreadsClient;
use Mockery as m;

class GoodreadsClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var GoodreadsClient */
    private $goodreadsClient;

    protected function setUp()
    {
        $this->client = m::mock('GuzzleHttp\Client');

        $this->goodreadsClient = m::mock(
            'Amoscato\Bundle\IntegrationBundle\Client\GoodreadsClient[createCrawler]',
            [
                $this->client,
                'key'
            ]
        );
    }

    protected function tearDown()
    {
        m::close();
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
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => 'body'
                    ]
                )
            );

        $this->goodreadsClient
            ->shouldReceive('createCrawler')
            ->with('body')
            ->andReturn(
                m::mock(
                    'Symfony\Component\DomCrawler\Crawler',
                    function($mock) {
                        /** @var m\Mock $mock */

                        $mock
                            ->shouldReceive('filter')
                            ->with('GoodreadsResponse reviews review')
                            ->andReturn('books');
                    }
                )
            );

        $this->assertSame(
            'books',
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
                    ]
                ]
            )
            ->andReturn(
                m::mock(
                    [
                        'getBody' => 'body'
                    ]
                )
            );

        $this->goodreadsClient
            ->shouldReceive('createCrawler')
            ->with('body')
            ->andReturn(
                m::mock(
                    'Symfony\Component\DomCrawler\Crawler',
                    function($mock) {
                        /** @var m\Mock $mock */

                        $mock
                            ->shouldReceive('filter')
                            ->with('GoodreadsResponse reviews review')
                            ->andReturn('books');
                    }
                )
            );

        $this->assertSame(
            'books',
            $this->goodreadsClient->getReadBooks(1)
        );
    }
}
