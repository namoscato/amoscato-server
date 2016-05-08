<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\IntegrationBundle\Client\Client;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

class LastfmSource extends Source
{
    /**
     * @var string
     */
    protected $type = 'lastfm';

    /**
     * @var \Amoscato\Bundle\IntegrationBundle\Client\LastfmClient
     */
    protected $client;

    /**
     * @var array
     */
    private $albumInfo;

    /**
     * @var string
     */
    private $user;

    /**
     * @param PDO $database
     * @param Client $client
     */
    public function __construct(PDO $database, Client $client)
    {
        $this->albumInfo = [];

        parent::__construct($database, $client);
    }

    /**
     * @param int $limit
     * @param int $page
     * @return array
     */
    protected function extract($limit = self::LIMIT, $page = 1)
    {
        return $this->client->getRecentTracks(
            $this->user,
            [
                'limit' => $limit,
                'page' => $page
            ]
        );
    }

    /**
     * @param object $item
     * @return array
     */
    protected function transform($item)
    {
        $albumId = $this->getAlbumId($item);

        $albumName = $item->album->{'#text'};
        $artistName = $item->artist->{'#text'};

        if (isset($this->albumInfo[$albumId])) {
            $album = $this->albumInfo[$albumId];
        } else {
            if (empty($item->album->mbid)) {
                $album = $this->client->getAlbumInfoByName($artistName, $albumName);
            } else {
                $album = $this->client->getAlbumInfoById($item->album->mbid);
            }

            $this->albumInfo[$albumId] = $album; // Cache album info
        }

        return [
            $this->getAlbumId($item, true),
            $item->image[3]->{'#text'},
            300,
            300,
            "\"{$albumName}\" by {$artistName}",
            empty($album->url) ? null : $album->url
        ];
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    public function load(OutputInterface $output)
    {
        /** @var \Amoscato\Console\ConsoleOutput $output */

        $count = 0;
        $page = 1;
        $previousAlbumId = null;
        $values = [];
        
        do {
            $tracks = $this->extract(200, $page++);

            foreach ($tracks as $track) {
                if (!isset($track->date)) {
                    continue; // Skip currently playing track
                }

                $albumId = $this->getAlbumId($track);

                if ($previousAlbumId === $albumId) {
                    continue; // Skip adjacent tracks on the same album
                }

                $values[] = $this->getType();

                $trackValues = $this->transform($track);

                $values = array_merge(
                    $values,
                    $trackValues
                );

                $output->writeVerbose("Transforming " . $this->getType() . " item: {$trackValues[4]}");

                $previousAlbumId = $albumId;
                $count++;
            }
        } while ($count < self::LIMIT);

        $statement = $this->statementProvider->insertRows($count);

        return $statement->execute($values);
    }

    /**
     * @param object $track
     * @param bool $isUnique optional
     * @return string
     */
    private function getAlbumId($track, $isUnique = false)
    {
        if (empty($track->album->mbid)) {
            $albumId = $track->album->{'#text'};
        } else {
            $albumId = $track->album->mbid;
        }

        if ($isUnique) {
            $albumId .= "-{$track->date->uts}";
        }

        return hash('md5', $albumId);
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
