<?php

namespace Tests\Bundle\AppBundle\Stream\Stream;

use Mockery as m;

class FlickrSourceTest extends \PHPUnit_Framework_TestCase
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
            'Amoscato\Bundle\AppBundle\Stream\Source\FlickrSource[getPhotoStatementProvider]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                $this->client
            ]
        );

        $this->source->setUserId(10);
        $this->source->setPhotoUri('flickr.com/');

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
    }

    protected function tearDown()
    {
        m::close();
    }

    public function test_load()
    {
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
                            'flickr'
                        );

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
            ->shouldReceive('getPublicPhotos')
            ->with(
                10,
                [
                    'extras' => 'url_m,path_alias,date_upload',
                    'page' => 1,
                    'per_page' => 100
                ]
            )
            ->andReturn(
                [
                    (object) [
                        'id' => 1,
                        'url_m' => 'img.jpg',
                        'width_m' => 'w',
                        'height_m' => 'h',
                        'title' => 'photo',
                        'pathalias' => 'user',
                        'dateupload' => '1463341026'
                    ]
                ]
            )
            ->shouldReceive('getPublicPhotos')
            ->andReturn([]);

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->with(1)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->with(m::mustBe([
                            'flickr',
                            '1',
                            'photo',
                            'flickr.com/user/1',
                            '2016-05-15 19:37:06',
                            'img.jpg',
                            'w',
                            'h',
                        ]));
                })
            );

        $this->source->load($this->output);
    }
}
