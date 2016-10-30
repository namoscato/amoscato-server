<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Mockery as m;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class UntappdSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\UntappdSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        m::mock(
            'alias:Carbon\Carbon',
            function($mock) {
                $mock
                    ->shouldReceive('parse')
                    ->with('created at')
                    ->andReturn(
                        m::mock(
                            [
                                'toDateTimeString' => 'date'
                            ]
                        )
                    );
            }
        );

        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');
        
        $this->source = m::mock(
            'Amoscato\Bundle\AppBundle\Stream\Source\UntappdSource[getStreamStatementProvider]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                $this->client
            ]
        );

        $this->source->setUsername('username');

        $this->statementProvider = m::mock('Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider');

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            'Symfony\Component\Console\Output\OutputInterface',
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
            ->with('untappd')
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
            ->shouldReceive('getUserBadges')
            ->with(
                'username',
                [
                    'offset' => 0,
                    'limit' => 50
                ]
            )
            ->andReturn(
                (object) [
                    'items' => [
                        (object) [
                            'badge_name' => 'badge',
                            'user_badge_id' => 'id',
                            'created_at' => 'created at',
                            'media' => (object) [
                                'badge_image_lg' => 'img.jpg'
                            ]
                        ]
                    ]
                ]
            )
            ->shouldReceive('getUserBadges')
            ->with(
                'username',
                [
                    'offset' => 50,
                    'limit' => 50
                ]
            )
            ->andReturn(
                (object) [
                    'items' => []
                ]
            );

        $this
            ->client
            ->shouldReceive('getBadgeUrl')
            ->with(
                'username',
                'id'
            )
            ->andReturn('badge url');

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
                            'untappd',
                            'id',
                            'badge',
                            'badge url',
                            'date',
                            'img.jpg',
                            400,
                            400
                        ]));
                })
            );

        $this->source->load($this->output);
    }
}
