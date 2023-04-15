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
        $currentlyReadingBooks = new Crawler(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <review>
        <book>
            <title>The Staff Engineer's Path</title>
            <link>https://www.goodreads.com/book/show/59694859-the-staff-engineer-s-path</link>
            <authors>
            <author>
                <name>Tanya Reilly</name>
            </author>
            </authors>
        </book>
        <started_at>Sun Jan 29 06:56:22 -0800 2023</started_at>
        </review>
        XML);

        $this
            ->client
            ->shouldReceive('getCurrentlyReadingBooks')
            ->andReturn($currentlyReadingBooks);

        self::assertEquals(
            [
                'author' => 'Tanya Reilly',
                'date' => '2023-01-29 14:56:22',
                'title' => "The Staff Engineer's Path",
                'url' => 'https://www.goodreads.com/book/show/59694859-the-staff-engineer-s-path',
            ],
            $this->target->load($this->output)
        );
    }
}
