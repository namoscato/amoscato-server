<?php

namespace Amoscato\Source\Current;

use Amoscato\Integration\Client\LastfmClient;
use Amoscato\Console\Output\ConsoleOutput;
use Carbon\Carbon;

class MusicSource implements CurrentSourceInterface
{
    /** @var LastfmClient */
    protected $client;

    /** @var string */
    private $user;

    /**
     * @param LastfmClient $client
     * @param string $user
     */
    public function __construct(LastfmClient $client, $user)
    {
        $this->client = $client;
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'music';
    }

    /**
     * {@inheritdoc}
     */
    public function load(ConsoleOutput $output, $limit = 1)
    {
        $tracks = $this->client->getRecentTracks(
            $this->user,
            [
                'limit' => 2
            ]
        );

        $i = 0;

        do {
            $track = $tracks[$i];

            if (!empty($track->artist->{'#text'})) {
                $date = isset($track->date) ? Carbon::createFromTimestampUTC($track->date->uts) : Carbon::now();

                return [
                    'album' => $track->album->{'#text'},
                    'artist' => $track->artist->{'#text'},
                    'date' => $date->toDateTimeString(),
                    'name' => $track->name,
                    'url' => $track->url
                ];
            }
        } while (isset($tracks[++$i]));

        return null;
    }
}
