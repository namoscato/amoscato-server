<?php

namespace Tests\Bundle\AppBundle\Current\Source;

use Amoscato\Bundle\AppBundle\Current\BookSource;
use Amoscato\Bundle\IntegrationBundle\Client\GoodreadsClient;
use Amoscato\Console\Output\ConsoleOutput;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class BookSourceTest extends TestCase
{
    /** @var BookSource */
    private $target;

    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $output;

    protected function setUp()
    {
        $this->client = m::mock(GoodreadsClient::class);

        $this->target = new BookSource($this->client, 1);

        $this->output = m::mock(ConsoleOutput::class);
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load_emptyResult()
    {
        $this
            ->client
            ->shouldReceive('getCurrentlyReadingBooks')
            ->with(
                1,
                [
                    'per_page' => 1
                ]
            )
            ->andReturn(
                m::mock(
                    Crawler::class,
                    [
                        'count' => 0
                    ]
                )
            );

        $this->assertEquals(
            null,
            $this->target->load($this->output)
        );
    }

    public function test_load()
    {
        $this
            ->client
            ->shouldReceive('getCurrentlyReadingBooks')
            ->andReturn(
                m::mock(
                    Crawler::class,
                    [
                        'count' => 1,
                        'first' => m::mock(
                            Crawler::class,
                            function($mock) {
                                /** @var m\Mock $mock */

                                $mock
                                    ->shouldReceive('filter')
                                    ->with('started_at')
                                    ->andReturn(
                                        m::mock(
                                            Crawler::class,
                                            [
                                                'text' => '2018-05-13 12:00:00',
                                            ]
                                        )
                                    );

                                $mock
                                    ->shouldReceive('filter')
                                    ->with('book')
                                    ->andReturn(
                                        m::mock(
                                            Crawler::class,
                                            function($mock) {
                                                /** @var m\Mock $mock */

                                                $mock
                                                    ->shouldReceive('filter')
                                                    ->with('authors')
                                                    ->andReturn(
                                                        m::mock(
                                                            Crawler::class,
                                                            [
                                                                'first->filter->text' => 'AUTHOR'
                                                            ]
                                                        )
                                                    );

                                                $mock
                                                    ->shouldReceive('filter')
                                                    ->with('title')
                                                    ->andReturn(
                                                        m::mock(
                                                            Crawler::class,
                                                            [
                                                                'text' => 'TITLE'
                                                            ]
                                                        )
                                                    );

                                                $mock
                                                    ->shouldReceive('filter')
                                                    ->with('link')
                                                    ->andReturn(
                                                        m::mock(
                                                            Crawler::class,
                                                            [
                                                                'text' => 'LINK'
                                                            ]
                                                        )
                                                    );
                                            }
                                        )
                                    );
                            }
                        )
                    ]
                )
            );

        $this->assertEquals(
            [
                'author' => 'AUTHOR',
                'date' => '2018-05-13 12:00:00',
                'title' => 'TITLE',
                'url' => 'LINK'
            ],
            $this->target->load($this->output)
        );
    }
}
