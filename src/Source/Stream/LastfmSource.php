<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\LastfmClient;
use Amoscato\Integration\Exception\LastfmBadResponseException;
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
    public function getType(): string
    {
        return 'lastfm';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     */
    protected function extract($perPage, PageIterator $iterator): array
    {
        return $this->client->getRecentTracks(
            $this->user,
            [
                'limit' => $perPage,
                'page' => $iterator->current(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function transform($item)
    {
        $album = $this->getAlbum($item, $item->album->mbid ?? null);

        $imageUrl = null;
        $imageWidth = null;
        $imageHeight = null;

        if (!empty($item->image[3]->{'#text'})) {
            $imageUrl = $item->image[3]->{'#text'};
            $imageWidth = 300;
            $imageHeight = 300;
        }

        return [
            "\"{$item->album->{'#text'}}\" by {$item->artist->{'#text'}}",
            $album->url ?? null,
            Carbon::createFromTimestampUTC($item->date->uts)->toDateTimeString(),
            $imageUrl,
            $imageWidth,
            $imageHeight,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(OutputInterface $output, $limit = 1): bool
    {
        $output = OutputDecorator::create($output);

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

                if (null !== $previousAlbumId && $previousAlbumId !== $albumId) { // Skip adjacent tracks on the same album
                    $sourceId = $this->getSourceId($previousTrack);

                    if ($latestSourceId === $sourceId) { // Break if item is already in database
                        $output->writeDebug("Item {$latestSourceId} is already in the database");
                        break 2;
                    }

                    $transformedTrack = $this->transform($previousTrack);

                    if (false === $transformedTrack) {
                        continue; // skip not found albums
                    }

                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $values = array_merge(
                        [
                            $this->getType(),
                            $sourceId,
                        ],
                        $transformedTrack,
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
     *
     * @return string
     */
    private function getAlbumId($track, $isUnique = false): string
    {
        if (empty($track->album->mbid)) {
            $albumId = "{$track->album->{'#text'}}-{$track->artist->{'#text'}}";
        } else {
            $albumId = $track->album->mbid;
        }

        if ($isUnique) {
            $albumId .= "-{$track->date->uts}";
        }

        return hash('md5', $albumId);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSourceId($item): string
    {
        return $this->getAlbumId($item, true);
    }

    /**
     * @param $item
     * @param string|null $musicbrainzId
     *
     * @return object
     */
    private function getAlbum($item, ?string $musicbrainzId = null)
    {
        $albumId = $this->getAlbumId($item);

        if (!empty($this->albumInfo[$albumId])) {
            return $this->albumInfo[$albumId];
        }

        if ($musicbrainzId) {
            try {
                return $this->albumInfo[$albumId] = $this->client->getAlbumInfoById($musicbrainzId);
            } catch (LastfmBadResponseException $exception) {
                if (LastfmBadResponseException::CODE_INVALID_PARAMETERS === $exception->getCode()) {
                    return $this->getAlbum($item); // try fetching by name
                }

                throw $exception;
            }
        }

        return $this->albumInfo[$albumId] = $this->client->getAlbumInfoByName($item->artist->{'#text'}, $item->album->{'#text'});
    }
}