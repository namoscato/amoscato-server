<?php

declare(strict_types=1);

namespace Tests\Source\Stream;

use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\GitHubClient;
use Amoscato\Source\Stream\GitHubSource;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
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

                    $mock->shouldReceive('execute')->andReturn(true);

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
                        'id' => '1',
                        'type' => 'PushEvent',
                        'repo' => (object) [
                            'name' => 'username/repo1',
                        ],
                        'payload' => (object) [
                            'before' => 'before4',
                            'head' => 'head4',
                        ],
                    ],
                    (object) [
                        'id' => '2',
                        'type' => 'AnotherEvent',
                    ],
                    (object) [
                        'id' => '3',
                        'type' => 'PushEvent',
                        'repo' => (object) [
                            'name' => 'username/repo2',
                        ],
                        'payload' => (object) [
                            'before' => 'before2',
                            'head' => 'head2',
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
                        'id' => '4',
                        'type' => 'PushEvent',
                        'repo' => (object) [
                            'name' => 'username/repo3',
                        ],
                        'payload' => (object) [
                            'before' => 'before0',
                            'head' => 'head0',
                        ],
                    ],
                ]
            )
            ->shouldReceive('getUserEvents')
            ->andReturn([]);

        $this->client
            ->shouldReceive('compareCommits')
            ->with('username', 'repo3', 'before0...head0')
            ->andReturn(
                (object) [
                    'commits' => [
                        (object) [
                            'sha' => '1',
                            'author' => (object) [
                                'login' => 'username',
                            ],
                            'commit' => (object) [
                                'message' => 'message 1',
                                'author' => (object) [
                                    'date' => '2016-05-17 21:00:00',
                                ],
                            ],
                            'html_url' => 'github.com/1',
                        ],
                        (object) [
                            'sha' => '2',
                            'author' => (object) [
                                'login' => 'username',
                            ],
                            'commit' => (object) [
                                'message' => "message 2\nwith newlines",
                                'author' => (object) [
                                    'date' => '2016-05-17 22:00:00',
                                ],
                            ],
                            'html_url' => 'github.com/2',
                        ],
                    ],
                ]
            );

        $this->client
            ->shouldReceive('compareCommits')
            ->with('username', 'repo2', 'before2...head2')
            ->andReturn(
                (object) [
                    'commits' => [
                        (object) [
                            'sha' => '2',
                            'author' => (object) [
                                'login' => 'username',
                            ],
                            'commit' => (object) [
                                'message' => "message 2\nwith newlines",
                                'author' => (object) [
                                    'date' => '2016-05-17 22:00:00',
                                ],
                            ],
                            'html_url' => 'github.com/2',
                        ],
                        (object) [
                            'sha' => '3',
                            'author' => (object) [
                                'login' => 'another user',
                            ],
                        ],
                    ],
                ]
            );

        $this->client
            ->shouldReceive('compareCommits')
            ->with('username', 'repo1', 'before4...head4')
            ->andReturn(
                (object) [
                    'commits' => [
                        (object) [
                            'sha' => '4',
                            'author' => (object) [
                                'login' => 'username',
                            ],
                            'commit' => (object) [
                                'message' => 'message 4',
                                'author' => (object) [
                                    'date' => '2016-05-17 23:00:00',
                                ],
                            ],
                            'html_url' => 'github.com/4',
                        ],
                    ],
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
                        'id' => '1',
                        'type' => 'PushEvent',
                        'repo' => (object) [
                            'name' => 'username/repo',
                        ],
                        'payload' => (object) [
                            'before' => 'before99',
                            'head' => 'head101',
                        ],
                    ],
                ]
            );

        $this->client
            ->shouldReceive('compareCommits')
            ->with('username', 'repo', 'before99...head101')
            ->andReturn(
                (object) [
                    'commits' => [
                        (object) [
                            'sha' => '99',
                            'author' => (object) [
                                'login' => 'username',
                            ],
                            'commit' => (object) [
                                'message' => 'message 99',
                                'author' => (object) [
                                    'date' => '2016-05-17 21:00:00',
                                ],
                            ],
                            'html_url' => 'github.com/99',
                        ],
                        (object) [
                            'sha' => '100',
                            'author' => (object) [
                                'login' => 'username',
                            ],
                            'commit' => (object) [
                                'message' => 'message 100',
                                'author' => (object) [
                                    'date' => '2016-05-17 22:00:00',
                                ],
                            ],
                            'html_url' => 'github.com/100',
                        ],
                        (object) [
                            'sha' => '101',
                            'author' => (object) [
                                'login' => 'username',
                            ],
                            'commit' => (object) [
                                'message' => 'message 101',
                                'author' => (object) [
                                    'date' => '2016-05-17 23:00:00',
                                ],
                            ],
                            'html_url' => 'github.com/101',
                        ],
                    ],
                ]
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
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
                        ]))
                        ->andReturn(true);
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
                ++$count;

                return [
                    (object) [
                        'id' => (string) $count,
                        'type' => 'PushEvent',
                        'repo' => (object) [
                            'name' => 'username/repo',
                        ],
                        'payload' => (object) [
                            'before' => "before{$count}",
                            'head' => "head{$count}",
                        ],
                    ],
                ];
            });

        $this->client
            ->shouldReceive('compareCommits')
            ->andReturnUsing(static function ($owner, $repo, $basehead) {
                // Extract count from basehead like "before1...head1"
                preg_match('/before(\d+)/', $basehead, $matches);
                $commitCount = $matches[1];

                return (object) [
                    'commits' => [
                        (object) [
                            'sha' => (string) $commitCount,
                            'author' => (object) [
                                'login' => 'username',
                            ],
                            'commit' => (object) [
                                'message' => "message {$commitCount}",
                                'author' => (object) [
                                    'date' => '2016-05-17 23:00:00',
                                ],
                            ],
                            'html_url' => 'github.com/x',
                        ],
                    ],
                ];
            });

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(10)
            ->andReturn(
                m::mock('PDOStatement', static function ($mock) {
                    /* @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->andReturn(true);
                })
            );

        $this->source->load($this->output, 10);
    }
}
