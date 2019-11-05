<?php

declare(strict_types=1);

namespace Tests\Source\Stream;

use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\TwitterClient;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use Amoscato\Source\Stream\TwitterSource;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TwitterSourceTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var TwitterSource */
    private $source;

    /** @var OutputInterface */
    private $output;

    protected function setUp()
    {
        $this->client = m::mock(TwitterClient::class);

        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider]', TwitterSource::class),
            [
                m::mock(PDOFactory::class),
                m::mock(FtpClient::class),
                $this->client,
                10,
                'twitter.com/',
            ]
        );

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = new NullOutput();
    }

    public function test_load()
    {
        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('twitter')
            ->andReturn(
                m::mock('PDOStatement', function ($mock) {
                    /* @var m\Mock $mock */

                    $mock->shouldReceive('execute');

                    $mock
                        ->shouldReceive('fetch')
                        ->andReturn(
                            [
                                'source_id' => '10',
                            ]
                        );
                })
            );

        $this->client
            ->shouldReceive('getUserTweets')
            ->with(
                10,
                [
                    'count' => 100,
                ]
            )
            ->andReturn(
                [
                    (object) [
                        'id_str' => '1',
                        'text' => 'tweet',
                        'created_at' => '2016-05-15 19:37:06',
                    ],
                ],
                []
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(1)
            ->andReturn(
                m::mock('PDOStatement', function ($mock) {
                    /* @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->with(m::mustBe([
                            'twitter',
                            '1',
                            'tweet',
                            'twitter.com/10/status/1',
                            '2016-05-15 19:37:06',
                            null,
                            null,
                            null,
                        ]));
                })
            );

        $this->source->load($this->output, 100);
    }
}
