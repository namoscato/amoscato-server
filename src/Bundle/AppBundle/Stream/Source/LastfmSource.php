<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\IntegrationBundle\Client\LastfmClient;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Console\Output\ConsoleOutput;
use Amoscato\Database\PDOFactory;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property LastfmClient $client
 */
class LastfmSource extends AbstractStreamSource
{
    /** @var array */
    private $albumInfo;

    /** @var string */
    private $user;

    /**
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param LastfmClient $client
     * @param string $user
     */
    public function __construct(
        PDOFactory $databaseFactory,
        FtpClient $ftpClient,
        LastfmClient $client,
        $user
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);

        $this->albumInfo = [];
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'lastfm';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage()
    {
        return 1000;
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
     * @param OutputInterface $output
     * @return array
     */
    protected function transform($item, OutputInterface $output)
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

        $imageUrl = null;
        $imageWidth = null;
        $imageHeight = null;

        if (!empty($item->image[3]->{'#text'})) {
            $imageUrl = $item->image[3]->{'#text'};
            $imageWidth = 300;
            $imageHeight = 300;
        }

        return [
            "\"{$albumName}\" by {$artistName}",
            empty($album->url) ? null : $album->url,
            Carbon::createFromTimestampUTC($item->date->uts)->toDateTimeString(),
            $imageUrl,
            $imageWidth,
            $imageHeight
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ConsoleOutput $output, $limit = 1)
    {
        $iterator = new PageIterator($limit);
        $previousAlbumId = null;
        $previousTrack = null;
        $values = [];

        $latestSourceId = $this->getLatestSourceId();
        $sourceId = null;

        while ($iterator->valid()) {
            $tracks = $this->extract($this->getPerPage(2 * $limit), $iterator);

            foreach ($tracks as $track) {
                if (!isset($track->date) || (empty($track->album->mbid) && empty($track->album->{'#text'}))) {
                    continue; // Skip currently playing track and tracks without album
                }

                $albumId = $this->getAlbumId($track);

                if ($previousAlbumId !== null && $previousAlbumId !== $albumId) { // Skip adjacent tracks on the same album
                    $sourceId = $this->getSourceId($previousTrack);

                    if ($latestSourceId === $sourceId) { // Break if item is already in database
                        $output->writeDebug("Item {$latestSourceId} is already in the database");
                        break 2;
                    }

                    $values = array_merge(
                        [
                            $this->getType(),
                            $sourceId
                        ],
                        $this->transform($previousTrack, $output),
                        $values
                    );

                    $output->writeVerbose("Transforming {$this->getType()} item: {$values[2]}");

                    $iterator->incrementCount();
                }

                $previousAlbumId = $albumId;
                $previousTrack = $track;
            }

            // TODO: $previousTrack should probably be inserted here in some edge context

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
}
