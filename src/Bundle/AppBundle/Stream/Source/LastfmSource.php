<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\IntegrationBundle\Client\Client;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Symfony\Component\Console\Output\OutputInterface;

class LastfmSource extends Source
{
    /** @var int */
    protected $perPage = 200;

    /** @var string */
    protected $type = 'lastfm';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\LastfmClient */
    protected $client;

    /** @var array */
    private $albumInfo;

    /** @var string */
    private $user;

    /**
     * @param PDOFactory $databaseFactory
     * @param Client $client
     */
    public function __construct(PDOFactory $databaseFactory, Client $client)
    {
        $this->albumInfo = [];

        parent::__construct($databaseFactory, $client);
    }

    /**
     * @param int $perPage
     * @param PageIterator $iterator optional
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        return $this->client->getRecentTracks(
            $this->user,
            [
                'limit' => $perPage,
                'page' => $iterator->current()
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
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        $iterator = new PageIterator(self::LIMIT);
        $previousAlbumId = null;
        $values = [];

        $latestSourceId = $this->getLatestSourceId();
        
        while ($iterator->valid()) {
            $tracks = $this->extract($this->perPage, $iterator);

            foreach ($tracks as $track) {
                if (!isset($track->date) || empty($track->image[3]->{'#text'})) { // Skip currently playing track and tracks without an image
                    continue;
                }

                $sourceId = $this->getSourceId($track);

                if ($latestSourceId === $sourceId) { // Break if item is already in database
                    $output->writeDebug("Item {$latestSourceId} is already in the database");
                    break 2;
                }

                $albumId = $this->getAlbumId($track);

                if ($previousAlbumId === $albumId) {
                    continue; // Skip adjacent tracks on the same album
                }

                $values = array_merge(
                    [
                        $this->getType(),
                        $sourceId
                    ],
                    $this->transform($track),
                    $values
                );

                $output->writeVerbose("Transforming " . $this->getType() . " item: {$values[5]}");

                $previousAlbumId = $albumId;
                $iterator->incrementCount();
            }

            $iterator->next();
        }

        return $this->insertValues($output, $iterator->getCount(), $values);
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
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return $this->getAlbumId($item, true);
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
