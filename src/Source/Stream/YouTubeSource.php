<?php

namespace Amoscato\Source\Stream;

use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\YouTubeClient;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param YouTubeClient $client
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
    public function getType()
    {
        return 'youtube';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage()
    {
        return 50;
    }

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        $pageToken = $iterator->current();

        if ($pageToken === 1) {
            $pageToken = null;
        }

        $response = $this->client->getPlaylistItems(
            $this->playlistId,
            [
                'maxResults' => $perPage,
                'pageToken' => $pageToken
            ]
        );

        $iterator->setNextPageValue($response->nextPageToken);

        return $response->items;
    }

    /**
     * {@inheritdoc}
     */
    protected function transform($item, OutputInterface $output)
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
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return $item->snippet->resourceId->videoId;
    }
}
