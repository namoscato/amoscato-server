<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\IntegrationBundle\Client\Client;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Carbon\Carbon;

class YouTubeSource extends Source
{
    /** @var int */
    protected $perPage = 50;

    /** @var string */
    protected $type = 'youtube';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\YouTubeClient */
    protected $client;

    /** @var string */
    private $playlistId;

    /** @var string */
    private $videoUri;

    /** @var string */
    private $nowDateTimeString;

    /**
     * @param PDOFactory $databaseFactory
     * @param Client $client
     */
    public function __construct(PDOFactory $databaseFactory, Client $client)
    {
        $this->nowDateTimeString = Carbon::now()->toDateTimeString();

        parent::__construct($databaseFactory, $client);
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
     * @param object $item
     * @return array
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
            $this->nowDateTimeString,
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

    /**
     * @param string $playlistId
     */
    public function setPlaylistId($playlistId)
    {
        $this->playlistId = $playlistId;
    }

    /**
     * @param string $videoUri
     */
    public function setVideoUri($videoUri)
    {
        $this->videoUri = $videoUri;
    }
}
