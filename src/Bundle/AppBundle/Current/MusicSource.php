<?php

namespace Amoscato\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Source\AbstractSource;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class MusicSource extends AbstractSource
{
    /** @var string */
    protected $type = 'lastfm';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\LastfmClient */
    protected $client;

    /** @var string */
    private $user;

    /**
     * @param OutputInterface $output
     * @return array
     */
    public function load(OutputInterface $output)
    {
        $tracks = $this->client->getRecentTracks(
            $this->user,
            [
                'limit' => 3
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

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
