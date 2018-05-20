<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Source\TwitterSource;
use Amoscato\Bundle\IntegrationBundle\Client\TwitterClient;
use Amoscato\Console\Output\ConsoleOutput;
use Mockery as m;
use Amoscato\Database\PDOFactory;
use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;

class TwitterSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var TwitterSource */
    private $source;

    /** @var m\Mock */
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
                'twitter.com/'
            ]
        );

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            ConsoleOutput::class,
            [
                'writeDebug' => null,
                'writeln' => null,
                'writeVerbose' => null
            ]
        );
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load()
    {
        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('twitter')
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock->shouldReceive('execute');

                    $mock
                        ->shouldReceive('fetch')
                        ->andReturn(
                            [
                                'source_id' => '10'
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
                        'created_at' => '2016-05-15 19:37:06'
                    ]
                ],
                []
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(1)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

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
