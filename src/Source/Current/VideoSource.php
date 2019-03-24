<?php

declare(strict_types=1);

namespace Amoscato\Source\Current;

use Amoscato\Integration\Client\VimeoClient;
use Amoscato\Integration\Client\YouTubeClient;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class VideoSource implements CurrentSourceInterface
{
    /** @var YouTubeClient */
    private $youTubeClient;

    /** @var VimeoClient */
    private $vimeoClient;

    /** @var string */
    private $youTubePlaylistId;

    /** @var string */
    private $youTubeVideoUri;

    /**
     * @param YouTubeClient $youTubeClient
     * @param string $youTubePlaylistId
     * @param string $youTubeVideoUri
     * @param VimeoClient $vimeoClient
     */
    public function __construct(
        YouTubeClient $youTubeClient,
        $youTubePlaylistId,
        $youTubeVideoUri,
        VimeoClient $vimeoClient
    ) {
        $this->youTubeClient = $youTubeClient;
        $this->youTubePlaylistId = $youTubePlaylistId;
        $this->youTubeVideoUri = $youTubeVideoUri;
        $this->vimeoClient = $vimeoClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'video';
    }

    /**
     * {@inheritdoc}
     */
    public function load(OutputInterface $output): ?array
    {
        $youTubeResponse = $this->youTubeClient->getPlaylistItems($this->youTubePlaylistId, ['maxResults' => 1]);

        $youTubeItem = $youTubeResponse->items[0];
        $youTubeDate = Carbon::parse($youTubeItem->snippet->publishedAt);

        $vimeoResponse = $this->vimeoClient->getLikes(['per_page' => 1]);

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
            'url' => $url,
        ];
    }
}
