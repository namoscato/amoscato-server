<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;

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
            $image->url,
            $image->width,
            $image->height,
            $item->snippet->title,
            "{$this->videoUri}{$item->snippet->resourceId->videoId}"
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
