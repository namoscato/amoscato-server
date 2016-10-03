<?php

namespace Amoscato\Bundle\AppBundle\Current\Source;

use Amoscato\Bundle\AppBundle\Source\SourceInterface;
use Amoscato\Bundle\IntegrationBundle\Client\Client;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class LastfmSource implements SourceInterface
{
    /** @var \Amoscato\Bundle\IntegrationBundle\Client\LastfmClient */
    private $client;

    /** @var string */
    private $user;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

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

    /**
     * @return string
     */
    public function getType()
    {
        return 'lastfm';
    }
}
