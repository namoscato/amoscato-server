<?php

namespace Amoscato\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Source\SourceInterface;
use Amoscato\Bundle\IntegrationBundle\Client\Client;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class VideoSource implements SourceInterface
{
    /** @var \Amoscato\Bundle\IntegrationBundle\Client\YouTubeClient */
    private $youTubeClient;

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\VimeoClient */
    private $vimeoClient;

    /** @var string */
    private $youTubePlaylistId;

    /** @var string */
    private $youTubeVideoUri;

    /**
     * @param Client $youTubeClient
     * @param Client $vimeoClient
     */
    public function __construct(Client $youTubeClient, Client $vimeoClient)
    {
        $this->youTubeClient = $youTubeClient;
        $this->vimeoClient = $vimeoClient;
    }

    /**
     * @param OutputInterface $output
     * @return array
     */
    public function load(OutputInterface $output)
    {
        $youTubeResponse = $this->youTubeClient->getPlaylistItems(
            $this->youTubePlaylistId,
            [
                'maxResults' => 1
            ]
        );

        $youTubeItem = $youTubeResponse->items[0];
        $youTubeDate = Carbon::parse($youTubeItem->snippet->publishedAt);

        $vimeoResponse = $this->vimeoClient->getLikes(
            [
                'per_page' => 1
            ]
        );

        $vimeoItem = $vimeoResponse->data[0];
        $vimeoDate = Carbon::parse($vimeoItem->metadata->interactions->like->added_time);

        if ($youTubeDate->gt($vimeoDate)) {
            $date = $youTubeDate->toDateTimeString();
            $title = $youTubeItem->snippet->title;
            $url = "{$this->youTubeVideoUri}{$youTubeItem->snippet->resourceId->videoId}";
        } else {
            $date = $vimeoDate->toDateTimeString();
            $title = $vimeoItem->name;
            $url = $vimeoItem->link;
        }

        return [
            'date' => $date,
            'title' => $title,
            'url' => $url
        ];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'video';
    }

    /**
     * @param string $youTubePlaylistId
     */
    public function setYouTubePlaylistId($youTubePlaylistId)
    {
        $this->youTubePlaylistId = $youTubePlaylistId;
    }

    /**
     * @param string $youTubeVideoUri
     */
    public function setYouTubeVideoUri($youTubeVideoUri)
    {
        $this->youTubeVideoUri = $youTubeVideoUri;
    }
}
