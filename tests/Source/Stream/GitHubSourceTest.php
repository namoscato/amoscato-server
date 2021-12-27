<?php

declare(strict_types=1);

namespace Tests\Source\Stream;

use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\GitHubClient;
use Amoscato\Source\Stream\GitHubSource;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class GitHubSourceTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var GitHubSource */
    private $source;

    /** @var OutputInterface */
    private $output;

    protected function setUp(): void
    {
        $this->client = m::mock(GitHubClient::class);

        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider]', GitHubSource::class),
            [
                m::mock(PDOFactory::class),
                $this->client,
                'username',
            ]
        );

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = new NullOutput();

        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('github')
            ->andReturn(
                m::mock('PDOStatement', static function ($mock) {
                    /* @var m\Mock $mock */

                    $mock->shouldReceive('execute');

                    $mock
                        ->shouldReceive('fetch')
                        ->andReturn(
                            [
                                'source_id' => '100',
                            ]
                        );
                })
            );
    }

    public function testLoadWithEmptyValues(): void
    {
        $this->client
            ->shouldReceive('getUserEvents')
            ->with(
                'username',
                [
                    'page' => 1,
                ]
            )
            ->andReturn([]);

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->never();

        self::assertTrue($this->source->load($this->output));
    }

    public function testLoadWithItems(): void
    {
        $this->client
            ->shouldReceive('getUserEvents')
            ->with(
                'username',
                [
                    'page' => 1,
                ]
            )
            ->andReturn(
                [
                    (object) [
                        'type' => 'PushEvent',
                        'payload' => (object) [
                            'commits' => [
                                (object) [
                                    'sha' => '4',
                                    'url' => 'api.github.com/4',
                                    'message' => 'message 4',
                                ],
                            ],
                        ],
                    ],
                    (object) [
                        'type' => 'AnotherEvent',
                    ],
                    (object) [
                        'type' => 'PushEvent',
                        'payload' => (object) [
                            'commits' => [
                                (object) [
                                    'sha' => '2',
                                    'url' => 'api.github.com/2',
                                    'message' => "message 2\nwith newlines",
                                ],
                                (object) [
                                    'sha' => '3',
                                    'url' => 'api.github.com/3',
                                    'message' => 'message 3',
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->shouldReceive('getUserEvents')
            ->with(
                'username',
                [
                    'page' => 2,
                ]
            )
            ->andReturn(
                [
                    (object) [
                        'type' => 'PushEvent',
                        'payload' => (object) [
                            'commits' => [
                                (object) [
                                    'sha' => '0',
                                    'url' => 'api.github.com/0',
                                    'message' => 'message 0',
                                ],
                                (object) [
                                    'sha' => '1',
                                    'url' => 'api.github.com/1',
                                    'message' => 'message 1',
                                ],
                                (object) [
                                    'sha' => '2',
                                    'url' => 'api.github.com/2',
                                    'message' => "message 2\nwith newlines",
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->shouldReceive('getUserEvents')
            ->andReturn([]);

        $this->client
            ->shouldReceive('getCommit')
            ->with('api.github.com/0')
            ->andThrow(
                new ClientException(
                    'message',
                    m::mock(RequestInterface::class),
                    new Response()
                )
            );

        $this->client
            ->shouldReceive('getCommit')
            ->with('api.github.com/1')
            ->andReturn(
                (object) [
                    'author' => (object) [
                        'login' => 'username',
                    ],
                    'commit' => (object) [
                        'author' => (object) [
                            'date' => '2016-05-17 21:00:00',
                        ],
                    ],
                    'html_url' => 'github.com/1',
                ]
            );

        $this->client
            ->shouldReceive('getCommit')
            ->with('api.github.com/2')
            ->andReturn(
                (object) [
                    'author' => (object) [
                        'login' => 'username',
                    ],
                    'commit' => (object) [
                        'author' => (object) [
                            'date' => '2016-05-17 22:00:00',
                        ],
                    ],
                    'html_url' => 'github.com/2',
                ]
            );

        $this->client
            ->shouldReceive('getCommit')
            ->with('api.github.com/3')
            ->andReturn(
                (object) [
                    'author' => (object) [
                        'login' => 'another user',
                    ],
                ]
            );

        $this->client
            ->shouldReceive('getCommit')
            ->with('api.github.com/4')
            ->andReturn(
                (object) [
                    'author' => (object) [
                        'login' => 'username',
                    ],
                    'commit' => (object) [
                        'author' => (object) [
                            'date' => '2016-05-17 23:00:00',
                        ],
                    ],
                    'html_url' => 'github.com/4',
                ]
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->with(3)
            ->andReturn(
                m::mock('PDOStatement', static function ($mock) {
                    /* @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->with(m::mustBe([
                            'github',
                            '1',
                            'message 1',
                            'github.com/1',
                            '2016-05-17 21:00:00',
                            null,
                            null,
                            null,

                            'github',
                            '2',
                            'message 2',
                            'github.com/2',
                            '2016-05-17 22:00:00',
                            null,
                            null,
                            null,

                            'github',
                            '4',
                            'message 4',
                            'github.com/4',
                            '2016-05-17 23:00:00',
                            null,
                            null,
                            null,
                        ]))
                        ->andReturn(true);
                })
            );

        self::assertTrue($this->source->load($this->output, 100));
    }

    public function testLoadWithPreviousItems(): void
    {
        $this->client
            ->shouldReceive('getUserEvents')
            ->andReturn(
                [
                    (object) [
                        'type' => 'PushEvent',
                        'payload' => (object) [
                            'commits' => [
                                (object) [
                                    'sha' => '99',
                                    'url' => 'api.github.com/99',
                                    'message' => 'message 99',
                                ],
                                (object) [
                                    'sha' => '100',
                                    'url' => 'api.github.com/100',
                                    'message' => 'message 100',
                                ],
                                (object) [
                                    'sha' => '101',
                                    'url' => 'api.github.com/101',
                                    'message' => 'message 101',
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this->client
            ->shouldReceive('getCommit')
            ->with('api.github.com/101')
            ->andReturn(
                (object) [
                    'author' => (object) [
                        'login' => 'username',
                    ],
                    'commit' => (object) [
                        'author' => (object) [
                            'date' => '2016-05-17 23:00:00',
                        ],
                    ],
                    'html_url' => 'github.com/101',
                ]
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->with(1)
            ->andReturn(
                m::mock('PDOStatement', static function ($mock) {
                    /* @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->with(m::mustBe([
                            'github',
                            '101',
                            'message 101',
                            'github.com/101',
                            '2016-05-17 23:00:00',
                            null,
                            null,
                            null,
                        ]));
                })
            );

        $this->source->load($this->output, 100);
    }

    public function testLoadWithMaxEventPages(): void
    {
        $count = 0;

        $this->client
            ->shouldReceive('getUserEvents')
            ->andReturnUsing(static function () use (&$count) {
                return [
                    (object) [
                        'type' => 'PushEvent',
                        'payload' => (object) [
                            'commits' => [
                                (object) [
                                    'sha' => (string) ++$count,
                                    'url' => "api.github.com/{$count}",
                                    'message' => "message {$count}",
                                ],
                            ],
                        ],
                    ],
                ];
            });

        $this->client
            ->shouldReceive('getCommit')
            ->andReturn(
                (object) [
                    'author' => (object) [
                        'login' => 'username',
                    ],
                    'commit' => (object) [
                        'author' => (object) [
                            'date' => '2016-05-17 23:00:00',
                        ],
                    ],
                    'html_url' => 'github.com/x',
                ]
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(10)
            ->andReturn(
                m::mock('PDOStatement', static function ($mock) {
                    /* @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once();
                })
            );

        $this->source->load($this->output, 100);
    }
}
