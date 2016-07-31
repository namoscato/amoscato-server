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
            'Amoscato\Bundle\AppBundle\Stream\Source\LastfmSource[getStreamStatementProvider]',
            [
                m::mock('Amoscato\Database\PDOFactory'),
                $this->client
            ]
        );

        $this->source->setUser('user');

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

        $this->statementProvider
            ->shouldReceive('selectLatestSourceId')
            ->with('lastfm')
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock->shouldReceive('execute');

                    $mock
                        ->shouldReceive('fetch')
                        ->andReturn(
                            [
                                'source_id' => '3e1c011ebbbde3ecaaa7704b0e543fb5' // mbid_2-tB
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

    public function test_load_with_items()
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
                            'uts' => '1463341026'
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
                    (object) [ // Adjacent track on the same album
                        'date' => (object) [
                            'uts' => '1463341006'
                        ],
                        'album' => (object) [
                            'mbid' => '',
                            '#text' => 'album three'
                        ],
                        'artist' => (object) [
                            '#text' => 'artist three'
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
                    (object) [ // Album with no image
                        'date' => (object) [
                            'uts' => '1463341016'
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
                                '#text' => ''
                            ]
                        ]
                    ],
                    (object) [ // Last track (will not get inserted at the moment)
                        'date' => (object) [
                            'uts' => '1463340996'
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
            ->once()
            ->with(2)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->with(m::mustBe([
                            'lastfm',
                            '74e8f4f589902bfac377ae294b9169f6',
                            '"album three" by artist three',
                            null,
                            '2016-05-15 19:36:56',
                            null,
                            null,
                            null,

                            'lastfm',
                            '298fb0003e79d1270b741ebb1703316b',
                            '"album two" by artist two',
                            'lastfm.com/album2',
                            '2016-05-15 19:37:06',
                            'image2.jpg',
                            300,
                            300,
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
        $this->client
            ->shouldReceive('getRecentTracks')
            ->andReturn(
                [
                    (object) [
                        'date' => (object) [
                            'uts' => '1363341030'
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
                            'uts' => '1363341020'
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
                            'uts' => '1363341010'
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
            ->once()
            ->with(1)
            ->andReturn(
                m::mock('PDOStatement', function($mock) {
                    /** @var m\Mock $mock */

                    $mock
                        ->shouldReceive('execute')
                        ->once()
                        ->with(m::mustBe([
                            'lastfm',
                            'a7c6d84df1967ae10e71402404b7df31',
                            '"album one" by artist one',
                            null,
                            '2013-03-15 09:50:30',
                            'image1.jpg',
                            300,
                            300,
                        ]));
                })
            );

        $this->source->load($this->output);
    }
}
