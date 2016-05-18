<?php

namespace Tests\Bundle\AppBundle\Stream\Stream;

use Mockery as m;

class SourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var \Tests\Mocks\Bundle\AppBundle\Stream\Source\MockSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');
        
        $this->source = m::mock(
            'Tests\Mocks\Bundle\AppBundle\Stream\Source\MockSource[getPhotoStatementProvider,mockTransform,mockExtract]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                $this->client
            ]
        );

        $this->statementProvider = m::mock('Amoscato\Bundle\AppBundle\Stream\Query\PhotoStatementProvider');

        $this->source
            ->shouldReceive('getPhotoStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            'Symfony\Component\Console\Output\OutputInterface',
            [
                'writeDebug' => null,
                'writeln' => null,
                'writeVerbose' => null
            ]
        );

        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('bindValue')
                        ->once()
                        ->with(
                            ':type',
                            'mockType'
                        );

                    $mock->shouldReceive('execute');

                    $mock
                        ->shouldReceive('fetch')
                        ->once()
                        ->with(2)
                        ->andReturn(
                            [
                                'source_id' => '5000'
                            ]
                        );
                })
            );
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load_with_empty_values()
    {
        $this->source
            ->shouldReceive('mockExtract')
            ->with(100, 1)
            ->andReturn([]);

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->never();

        $this->assertSame(
            true,
            $this->source->load($this->output)
        );
    }

    public function test_load_with_items()
    {
        $this->source
            ->shouldReceive('mockExtract')
            ->with(100, 1)
            ->andReturn(
                [
                    (object) [
                        'id' => 1
                    ],
                    (object) [
                        'id' => 2
                    ],
                    (object) [
                        'id' => 3
                    ],
                ]
            )
            ->shouldReceive('mockExtract')
            ->with(100, 2)
            ->andReturn(
                [
                    (object) [
                        'id' => 4
                    ],
                    (object) [
                        'id' => 5
                    ],
                    (object) [
                        'id' => 6
                    ],
                ]
            )
            ->shouldReceive('mockExtract')
            ->with(100, 3)
            ->andReturn([]);

        $this->source
            ->shouldReceive('mockTransform')
            ->andReturnUsing(function($item) {
                if ($item->id === 6) {
                    return false;
                }

                return [
                    "value-{$item->id}-1",
                    "value-{$item->id}-2",
                    "value-{$item->id}-3",
                    "value-{$item->id}-4",
                ];
            });

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(5)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->with(m::mustBe([
                            'mockType',
                            '5',
                            'value-5-1',
                            'value-5-2',
                            'value-5-3',
                            'value-5-4',

                            'mockType',
                            '4',
                            'value-4-1',
                            'value-4-2',
                            'value-4-3',
                            'value-4-4',

                            'mockType',
                            '3',
                            'value-3-1',
                            'value-3-2',
                            'value-3-3',
                            'value-3-4',

                            'mockType',
                            '2',
                            'value-2-1',
                            'value-2-2',
                            'value-2-3',
                            'value-2-4',

                            'mockType',
                            '1',
                            'value-1-1',
                            'value-1-2',
                            'value-1-3',
                            'value-1-4',
                        ]))
                        ->andReturn(true);

                })
            );

        $this->assertSame(
            true,
            $this->source->load($this->output)
        );
    }

    public function test_load_with_previous_items()
    {
        $this->source
            ->shouldReceive('mockExtract')
            ->with(100, 1)
            ->andReturn(
                [
                    (object) [
                        'id' => 5001
                    ],
                    (object) [
                        'id' => 5000
                    ],
                    (object) [
                        'id' => 4999
                    ],
                ]
            );

        $this->source
            ->shouldReceive('mockTransform')
            ->andReturn(
                [
                    1,
                    2,
                    3,
                    4
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
                            'mockType',
                            '5001',
                            1,
                            2,
                            3,
                            4
                        ]));
                })
            );

        $this->source->load($this->output);
    }
}
