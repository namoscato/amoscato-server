<?php

namespace Tests\Bundle\AppBundle\Stream\Stream;

use Mockery as m;

class LastfmSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var m\Mock */
    private $client;

    /** @var m\Mock */
    private $statementProvider;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\LastfmSource */
    private $source;

    /** @var m\Mock */
    private $output;
    
    protected function setUp()
    {
        $this->client = m::mock('Amoscato\Bundle\IntegrationBundle\Client\Client');
        
        $this->source = m::mock(
            'Amoscato\Bundle\AppBundle\Stream\Source\LastfmSource[getPhotoStatementProvider]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                $this->client
            ]
        );
        
        $this->source->setUser('user');

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
                            'lastfm'
                        );

                    $mock->shouldReceive('execute');

                    $mock
                        ->shouldReceive('fetch')
                        ->andReturn(
                            [
                                'source_id' => '23c3351c292bc77a4d39dadb42988410' // mbid_2-tB
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
        $this->client
            ->shouldReceive('getRecentTracks')
            ->with(
                'user',
                [
                    'limit' => 200,
                    'page' => 1
                ]
            )
            ->andReturn([]);

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->never();

        $this->assertSame(
            true,
            $this->source->load($this->output)
        );
    }

    public function test_load_with_less_than_100_values()
    {
        $this->client
            ->shouldReceive('getRecentTracks')
            ->with(
                'user',
                [
                    'limit' => 200,
                    'page' => 1
                ]
            )
            ->andReturn(
                [
                    (object) [ // Currently playing track
                        'album' => (object) [
                            'mbid' => 1
                        ]
                    ],
                    (object) [
                        'date' => (object) [
                            'uts' => 'tA'
                        ],
                        'album' => (object) [
                            'mbid' => 2,
                            '#text' => 'album two'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist two'
                        ],
                        'image' => [
                            0,
                            1,
                            2,
                            (object) [
                                '#text' => 'image2.jpg'
                            ]
                        ]
                    ],
                    (object) [
                        'date' => (object) [
                            'uts' => 'tB'
                        ],
                        'album' => (object) [
                            'mbid' => '',
                            '#text' => 'album three'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist three'
                        ],
                        'image' => [
                            0,
                            1,
                            2,
                            (object) [
                                '#text' => 'image3.jpg'
                            ]
                        ]
                    ],
                ]
            )
            ->shouldReceive('getRecentTracks')
            ->with(
                'user',
                [
                    'limit' => 200,
                    'page' => 2
                ]
            )
            ->andReturn(
                [
                    (object) [ // Adjacent track on the same album
                        'date' => (object) [
                            'uts' => 'tC'
                        ],
                        'album' => (object) [
                            'mbid' => '',
                            '#text' => 'album three'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist three'
                        ]
                    ],
                    (object) [
                        'date' => (object) [
                            'uts' => 'tD'
                        ],
                        'album' => (object) [
                            'mbid' => 2,
                            '#text' => 'album two'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist two'
                        ],
                        'image' => [
                            0,
                            1,
                            2,
                            (object) [
                                '#text' => 'image2.jpg'
                            ]
                        ]
                    ],
                ]
            )
            ->shouldReceive('getRecentTracks')
            ->andReturn([]);

        $this->client
            ->shouldReceive('getAlbumInfoById')
            ->once()
            ->with(2)
            ->andReturn(
                (object) [
                    'url' => 'lastfm.com/album2'
                ]
            );

        $this->client
            ->shouldReceive('getAlbumInfoByName')
            ->once()
            ->with(
                'artist three',
                'album three'
            )
            ->andReturn(
                (object) []
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->with(3)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->with(m::mustBe([
                            'lastfm',
                            '4b36111269579316ae736e6ccf2174d4',
                            'image2.jpg',
                            300,
                            300,
                            '"album two" by artist two',
                            'lastfm.com/album2',

                            'lastfm',
                            '5f4c75e2a86bffbec2c31d1446f80f86',
                            'image3.jpg',
                            300,
                            300,
                            '"album three" by artist three',
                            null,

                            'lastfm',
                            '4bee2ffffcc6b58157640362b813666d',
                            'image2.jpg',
                            300,
                            300,
                            '"album two" by artist two',
                            'lastfm.com/album2',
                        ]))
                        ->andReturn(true);

                })
            );

        $this->assertSame(
            true,
            $this->source->load($this->output)
        );
    }

    public function test_load_with_over_100_values()
    {
        $this->client
            ->shouldReceive('getRecentTracks')
            ->with(
                'user',
                [
                    'limit' => 200,
                    'page' => 1
                ]
            )
            ->andReturnUsing(function() {
                $result = [];

                for ($i = 1; $i <= 100; $i++) {
                    $result[] = (object) [
                        'date' => (object) [
                            'uts' => 'time'
                        ],
                        'album' => (object) [
                            'mbid' => $i,
                            '#text' => 'album'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist'
                        ],
                        'image' => [
                            0,
                            1,
                            2,
                            (object) [
                                '#text' => 'image.jpg'
                            ]
                        ]
                    ];
                }

                return $result;
            });

        $this->client
            ->shouldReceive('getAlbumInfoById')
            ->andReturn(
                (object) []
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->once()
            ->with(100)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock->shouldReceive('execute');
                })
            );

        $this->source->load($this->output);
    }

    public function test_load_with_previous_items()
    {
        $this->client
            ->shouldReceive('getRecentTracks')
            ->andReturn(
                [
                    (object) [
                        'date' => (object) [
                            'uts' => 'tA'
                        ],
                        'album' => (object) [
                            'mbid' => 'mbid_1',
                            '#text' => 'album one'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist one'
                        ],
                        'image' => [
                            0,
                            1,
                            2,
                            (object) [
                                '#text' => 'image1.jpg'
                            ]
                        ]
                    ],
                    (object) [
                        'date' => (object) [
                            'uts' => 'tB'
                        ],
                        'album' => (object) [
                            'mbid' => 'mbid_2',
                            '#text' => 'album two'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist two'
                        ],
                        'image' => [
                            0,
                            1,
                            2,
                            (object) [
                                '#text' => 'image2.jpg'
                            ]
                        ]
                    ],
                    (object) [
                        'date' => (object) [
                            'uts' => 'tC'
                        ],
                        'album' => (object) [
                            'mbid' => 'mbid_3',
                            '#text' => 'album three'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist three'
                        ],
                        'image' => [
                            0,
                            1,
                            2,
                            (object) [
                                '#text' => 'image3.jpg'
                            ]
                        ]
                    ],
                ]
            );

        $this->client
            ->shouldReceive('getAlbumInfoById')
            ->andReturn(
                (object) []
            );

        $this->statementProvider
            ->shouldReceive('insertRows')
            ->with(1)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->with(m::mustBe([
                            'lastfm',
                            '8a0a5260c6c72ff26ce243f632e92ab6',
                            'image1.jpg',
                            300,
                            300,
                            '"album one" by artist one',
                            null
                        ]));
                })
            );

        $this->source->load($this->output);
    }
}
