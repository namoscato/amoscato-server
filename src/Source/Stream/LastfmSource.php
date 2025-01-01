<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Database\PDOFactory;
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
    private $albumIdUrlMap = [];

    /** @var string */
    private $user;

    public function __construct(
        PDOFactory $databaseFactory,
        LastfmClient $client,
        string $user
    ) {
        parent::__construct($databaseFactory, $client);

        $this->user = $user;
    }

    public function getType(): string
    {
        return 'lastfm';
    }

    protected function getMaxPerPage(): int
    {
        return 1000;
    }

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

    protected function transform($item)
    {
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
            $this->getAlbumUrl($item, $item->album->mbid ?? null),
            Carbon::createFromTimestampUTC($item->date->uts)->toDateTimeString(),
            $imageUrl,
            $imageWidth,
            $imageHeight,
        ];
    }

    public function load(OutputInterface $output, int $limit = 1): bool
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

    private function getAlbumId(object $track, bool $isUnique = false): string
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

    protected function getSourceId($item): string
    {
        return $this->getAlbumId($item, true);
    }

    private function getAlbumUrl(object $item, string $musicbrainzId = null): ?string
    {
        $albumId = $this->getAlbumId($item);

        if (array_key_exists($albumId, $this->albumIdUrlMap)) {
            return $this->albumIdUrlMap[$albumId];
        }

        try {
            $album = $musicbrainzId ?
                $this->client->getAlbumInfoById($musicbrainzId) :
                $this->client->getAlbumInfoByName($item->artist->{'#text'}, $item->album->{'#text'});

            return $this->albumIdUrlMap[$albumId] = $album->url ?? null;
        } catch (LastfmBadResponseException $exception) {
            if ($musicbrainzId && LastfmBadResponseException::CODE_INVALID_PARAMETERS === $exception->getCode()) {
                return $this->getAlbumUrl($item); // try fetching by name
            }

            return null;
        }
    }
}
