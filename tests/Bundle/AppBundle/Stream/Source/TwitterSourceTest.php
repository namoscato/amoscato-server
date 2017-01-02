<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Mockery as m;

class TwitterSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\TwitterSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');
        
        $this->source = m::mock(
            'Amoscato\Bundle\AppBundle\Stream\Source\TwitterSource[getStreamStatementProvider]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                m::mock('\Amoscato\Bundle\AppBundle\Ftp\FtpClient'),
                $this->client
            ]
        );

        $this->source->setScreenName(10);
        $this->source->setStatusUri('twitter.com/');

        $this->statementProvider = m::mock('Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider');

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            'Symfony\Component\Console\Output\OutputInterface',
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

        $this->source->load($this->output);
    }
}
