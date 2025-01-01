<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\YouTubeClient;
use Carbon\Carbon;

/**
 * @property YouTubeClient $client
 */
class YouTubeSource extends AbstractStreamSource
{
    /** @var string */
    private $playlistId;

    /** @var string */
    private $videoUri;

    /**
     * @param string $playlistId
     * @param string $videoUri
     */
    public function __construct(
        PDOFactory $databaseFactory,
        YouTubeClient $client,
        $playlistId,
        $videoUri,
    ) {
        parent::__construct($databaseFactory, $client);

        $this->playlistId = $playlistId;
        $this->videoUri = $videoUri;
    }

    public function getType(): string
    {
        return 'youtube';
    }

    protected function getMaxPerPage(): int
    {
        return 50;
    }

    protected function extract($perPage, PageIterator $iterator): array
    {
        $pageToken = $iterator->current();

        if (1 === $pageToken) {
            $pageToken = null;
        }

        $response = $this->client->getPlaylistItems(
            $this->playlistId,
            [
                'maxResults' => $perPage,
                'pageToken' => $pageToken,
            ]
        );

        $iterator->setNextPageValue($response->nextPageToken);

        return $response->items;
    }

    protected function transform($item)
    {
        if (!isset($item->snippet->thumbnails)) {
            return false;
        }

        $image = $item->snippet->thumbnails->medium;

        return [
            $item->snippet->title,
            "{$this->videoUri}{$item->snippet->resourceId->videoId}",
            Carbon::parse($item->snippet->publishedAt)->toDateTimeString(),
            $image->url,
            $image->width,
            $image->height,
        ];
    }

    protected function getSourceId($item): string
    {
        return $item->snippet->resourceId->videoId;
    }
}
