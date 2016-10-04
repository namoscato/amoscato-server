<?php

namespace Tests\Bundle\AppBundle\Current\Source;

use Amoscato\Bundle\AppBundle\Current\BookSource;
use Mockery as m;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BookSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var BookSource */
    private $target;

    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $output;

    protected function setUp()
    {
        m::mock(
            'alias:Carbon\Carbon',
            [
                'parse->setTimezone->toDateTimeString' => 'DATE'
            ]
        );

        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');

        $this->target = new BookSource($this->client);

        $this
            ->target
            ->setUserId(1);

        $this->output = m::mock('Symfony\Component\Console\Output\OutputInterface');
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
                    'Symfony\Component\DomCrawler\Crawler',
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
                    'Symfony\Component\DomCrawler\Crawler',
                    [
                        'count' => 1,
                        'first' => m::mock(
                            'Symfony\Component\DomCrawler\Crawler',
                            function($mock) {
                                $mock
                                    ->shouldReceive('filter')
                                    ->with('started_at')
                                    ->andReturn(
                                        m::mock(
                                            'Symfony\Component\DomCrawler\Crawler',
                                            [
                                                'text' => '2016'
                                            ]
                                        )
                                    );

                                $mock
                                    ->shouldReceive('filter')
                                    ->with('book')
                                    ->andReturn(
                                        m::mock(
                                            'Symfony\Component\DomCrawler\Crawler',
                                            function($mock) {
                                                $mock
                                                    ->shouldReceive('filter')
                                                    ->with('authors')
                                                    ->andReturn(
                                                        m::mock(
                                                            'Symfony\Component\DomCrawler\Crawler',
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
                                                            'Symfony\Component\DomCrawler\Crawler',
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
                                                            'Symfony\Component\DomCrawler\Crawler',
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
                'date' => 'DATE',
                'title' => 'TITLE',
                'url' => 'LINK'
            ],
            $this->target->load($this->output)
        );
    }
}
