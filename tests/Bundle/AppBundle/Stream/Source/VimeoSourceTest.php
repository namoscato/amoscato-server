<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Source\VimeoSource;
use Amoscato\Bundle\IntegrationBundle\Client\VimeoClient;
use Amoscato\Console\Output\ConsoleOutput;
use Mockery as m;
use Amoscato\Database\PDOFactory;
use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;

class VimeoSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var VimeoSource */
    private $source;

    /** @var m\Mock */
    private $output;

    protected function setUp()
    {
        $this->client = m::mock(VimeoClient::class);
        
        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider]', VimeoSource::class),
            [
                m::mock(PDOFactory::class),
                m::mock(FtpClient::class),
                $this->client,
            ]
        );

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            ConsoleOutput::class,
            [
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
            ->with('vimeo')
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
            ->shouldReceive('getLikes')
            ->with(
                [
                    'page' => 1,
                    'per_page' => 50
                ]
            )
            ->andReturn(
                (object) [
                    'paging' => (object) [
                        'next' => 2
                    ],
                    'data' => [
                        (object) [
                            'uri' => '/videos/123',
                            'name' => 'video1',
                            'link' => 'link1',
                            'metadata' => (object) [
                                'interactions' => (object) [
                                    'like' => (object) [
                                        'added_time' => '2013-03-15 09:50:30'
                                    ]
                                ]
                            ],
                            'pictures' => (object) [
                                'sizes' => [
                                    0,
                                    1,
                                    (object) [
                                        'link' => 'img.jpg',
                                        'width' => 300,
                                        'height' => 100
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            );

        $this
            ->client
            ->shouldReceive('getLikes')
            ->with(
                [
                    'page' => 2,
                    'per_page' => 50
                ]
            )
            ->andReturn(
                (object) [
                    'paging' => (object) [
                        'next' => null
                    ],
                    'data' => []
                ]
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
                            'vimeo',
                            '123',
                            'video1',
                            'link1',
                            '2013-03-15 09:50:30',
                            'img.jpg',
                            300,
                            100,
                        ]));
                })
            );

        $this->source->load($this->output, 100);
    }
}
