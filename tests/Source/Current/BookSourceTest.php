<?php

declare(strict_types=1);

namespace Tests\Source\Current;

use Amoscato\Integration\Client\GoodreadsClient;
use Amoscato\Source\Current\BookSource;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class BookSourceTest extends MockeryTestCase
{
    /** @var BookSource */
    private $target;

    /** @var m\Mock */
    private $client;

    /** @var OutputInterface */
    private $output;

    protected function setUp(): void
    {
        $this->client = m::mock(GoodreadsClient::class);

        $this->target = new BookSource($this->client, 1);

        $this->output = new NullOutput();
    }

    public function testLoadEmptyResult(): void
    {
        $this
            ->client
            ->shouldReceive('getCurrentlyReadingBooks')
            ->with(
                1,
                [
                    'per_page' => 1,
                ]
            )
            ->andReturn(
                m::mock(
                    Crawler::class,
                    [
                        'count' => 0,
                    ]
                )
            );

        self::assertEquals(
            null,
            $this->target->load($this->output)
        );
    }

    public function testLoad(): void
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
                            static function ($mock) {
                                /* @var m\Mock $mock */

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
                                            static function ($mock) {
                                                /* @var m\Mock $mock */

                                                $mock
                                                    ->shouldReceive('filter')
                                                    ->with('authors')
                                                    ->andReturn(
                                                        m::mock(
                                                            Crawler::class,
                                                            [
                                                                'first->filter->text' => 'AUTHOR',
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
                                                                'text' => 'TITLE',
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
                                                                'text' => 'LINK',
                                                            ]
                                                        )
                                                    );
                                            }
                                        )
                                    );
                            }
                        ),
                    ]
                )
            );

        self::assertEquals(
            [
                'author' => 'AUTHOR',
                'date' => '2018-05-13 12:00:00',
                'title' => 'TITLE',
                'url' => 'LINK',
            ],
            $this->target->load($this->output)
        );
    }
}
