<?php

declare(strict_types=1);

namespace Tests\Source\Stream;

use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\FlickrClient;
use Amoscato\Source\Stream\FlickrSource;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class FlickrSourceTest extends MockeryTestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var FlickrSource */
    private $source;

    /** @var OutputInterface */
    private $output;

    protected function setUp(): void
    {
        $this->client = m::mock(FlickrClient::class);

        $this->source = m::mock(
            sprintf('%s[getStreamStatementProvider]', FlickrSource::class),
            [
                m::mock(PDOFactory::class),
                $this->client,
                10,
                'flickr.com/',
            ]
        );

        $this->statementProvider = m::mock(StreamStatementProvider::class);

        $this->source
            ->shouldReceive('getStreamStatementProvider')
            ->andReturn($this->statementProvider);

        $this->output = new NullOutput();
    }

    public function testLoad(): void
    {
        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('flickr')
            ->andReturn(
                m::mock('PDOStatement', static function ($mock) {
                    /* @var m\Mock $mock */

                    $mock->shouldReceive('execute')->andReturn(true);

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
            ->shouldReceive('getPublicPhotos')
            ->with(
                10,
                [
                    'extras' => 'url_m,path_alias,date_upload',
                    'page' => 1,
                    'per_page' => 100,
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
                        'dateupload' => '1463341026',
                    ],
                ]
            )
            ->shouldReceive('getPublicPhotos')
            ->andReturn([]);

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
                            'flickr',
                            '1',
                            'photo',
                            'flickr.com/user/1',
                            '2016-05-15 19:37:06',
                            'img.jpg',
                            'w',
                            'h',
                        ]))
                        ->andReturn(true);
                })
            );

        $this->source->load($this->output, 100);
    }
}
