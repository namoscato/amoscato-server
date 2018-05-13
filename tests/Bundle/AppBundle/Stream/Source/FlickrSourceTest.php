<?php

namespace Tests\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Source\FlickrSource;
use Amoscato\Bundle\IntegrationBundle\Client\FlickrClient;
use Mockery as m;
use Amoscato\Database\PDOFactory;
use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Symfony\Component\Console\Output\OutputInterface;

class FlickrSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var FlickrSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock(FlickrClient::class);
        
        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider]', FlickrSource::class),
            [
                m::mock(PDOFactory::class),
                m::mock(FtpClient::class),
                $this->client,
                10,
                'flickr.com/',
            ]
        );

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = m::mock(
            OutputInterface::class,
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
            ->with('flickr')
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
            ->once()
            ->with(1)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
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
