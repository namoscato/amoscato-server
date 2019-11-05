<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
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
        FtpClient $ftpClient,
        YouTubeClient $client,
        $playlistId,
        $videoUri
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);

        $this->playlistId = $playlistId;
        $this->videoUri = $videoUri;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'youtube';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage(): int
    {
        return 50;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function getSourceId($item): string
    {
        return $item->snippet->resourceId->videoId;
    }
}
