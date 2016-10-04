<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Mockery as m;

class FoodspottingSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\FlickrSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');
        
        $this->source = m::mock(
            'Amoscato\Bundle\AppBundle\Stream\Source\FoodspottingSource[getStreamStatementProvider]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                $this->client
            ]
        );

        $this->source->setPersonId(10);
        $this->source->setReviewUri('foodspotting.com/');

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
            ->with('foodspotting')
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
            ->shouldReceive('getReviews')
            ->with(
                10,
                [
                    'page' => 1,
                    'per_page' => 20,
                    'sort' => 'latest'
                ]
            )
            ->andReturn(
                [
                    (object) [
                        'id' => 1,
                        'thumb_280' => 'img.jpg',
                        'item' => (object) [
                            'name' => 'item name'
                        ],
                        'place' => (object) [
                            'name' => 'place name'
                        ],
                        'created_at' => '2016-05-15 19:37:06'
                    ]
                ]
            )
            ->shouldReceive('getReviews')
            ->andReturn([]);

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
                            'foodspotting',
                            '1',
                            'item name at place name',
                            'foodspotting.com/1',
                            '2016-05-15 19:37:06',
                            'img.jpg',
                            280,
                            280,
                        ]));
                })
            );

        $this->source->load($this->output);
    }
}
