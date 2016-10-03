<?php

namespace Amoscato\Bundle\AppBundle\Current\Source;

use Amoscato\Bundle\AppBundle\Source\AbstractSource;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class LastfmSource extends AbstractSource
{
    /** @var \Amoscato\Bundle\IntegrationBundle\Client\LastfmClient */
    protected $client;

    /** @var string */
    protected $type = 'lastfm';

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
                    'name' => $track->name,
                    'artist' => $track->artist->{'#text'},
                    'album' => $track->album->{'#text'},
                    'url' => $track->url,
                    'date' => $date->toDateTimeString()
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
