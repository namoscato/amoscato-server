<?php

declare(strict_types=1);

namespace Tests\Source\Stream;

use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\Client;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use ArrayObject;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use PDOStatement;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Mocks\Source\Stream\MockSource;

class AbstractStreamSourceTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var MockSource */
    private $source;

    /** @var OutputInterface */
    private $output;

    protected function setUp(): void
    {
        $this->client = m::mock(Client::class);

        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider,mockTransform,mockExtract]', MockSource::class),
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

        $this->output = new NullOutput();

        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('mockType')
            ->andReturn(
                m::mock('PDOStatement', static function ($mock) {
                    /* @var m\Mock $mock */

                    $mock->shouldReceive('execute');

                    $mock
                        ->shouldReceive('fetch')
                        ->once()
                        ->with(2)
                        ->andReturn(
                            [
                                'source_id' => '5000',
                            ]
                        );
                })
            );
    }

    public function test_load_with_empty_values(): void
    {
        $this->source
            ->shouldReceive('mockExtract')
            ->with(100, 1)
            ->andReturn([]);

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->never();

        self::assertTrue($this->source->load($this->output, 100));
    }

    public function test_load_with_items(): void
    {
        $this->source
            ->shouldReceive('mockExtract')
            ->with(100, 1)
            ->andReturn(
                [
                    (object) [
                        'id' => 1,
                    ],
                    (object) [
                        'id' => 2,
                    ],
                    (object) [
                        'id' => 3,
                    ],
                ]
            )
            ->shouldReceive('mockExtract')
            ->with(100, 2)
            ->andReturn(
                [
                    (object) [
                        'id' => 4,
                    ],
                    (object) [
                        'id' => 5,
                    ],
                    (object) [
                        'id' => 6,
                    ],
                ]
            )
            ->shouldReceive('mockExtract')
            ->with(100, 3)
            ->andReturn([]);

        $this->source
            ->shouldReceive('mockTransform')
            ->andReturnUsing(static function ($item) {
                if (6 === $item->id) {
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
                m::mock('PDOStatement', static function ($mock) {
                    /* @var m\Mock $mock */

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

        self::assertTrue($this->source->load($this->output, 100));
    }

    public function test_load_with_previous_items(): void
    {
        $this->source
            ->shouldReceive('mockExtract')
            ->with(100, 1)
            ->andReturn(
                [
                    (object) [
                        'id' => 5001,
                    ],
                    (object) [
                        'id' => 5000,
                    ],
                    (object) [
                        'id' => 4999,
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
                    4,
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
                        ->once()
                        ->with(m::mustBe([
                            'mockType',
                            '5001',
                            1,
                            2,
                            3,
                            4,
                        ]));
                })
            );

        $this->source->load($this->output, 100);
    }

    public function test_load_with_multiple_transformed_items(): void
    {
        $this
            ->source
            ->shouldReceive('mockExtract')
            ->andReturn([
                (object) ['id' => 5001],
                (object) ['id' => 5000],
            ]);

        $this->source
            ->shouldReceive('mockTransform')
            ->andReturn(new ArrayObject([
                [
                    1,
                    2,
                    3,
                    4,
                ],
                [
                    5,
                    6,
                    7,
                    8,
                ],
            ]));

        $this
            ->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(2)
            ->andReturn(m::mock(
                PDOStatement::class,
                static function ($mock) {
                    /* @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->with([
                            'mockType',
                            '5001_1',
                            5,
                            6,
                            7,
                            8,

                            'mockType',
                            '5001',
                            1,
                            2,
                            3,
                            4,
                        ]);
                }
            ));

        $this->source->load($this->output, 100);
    }
}
