<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;
use Carbon\Carbon;

class TwitterSource extends Source
{
    /** @var string */
    protected $type = 'twitter';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\TwitterClient */
    protected $client;

    /** @var string */
    private $screenName;

    /** @var string */
    private $statusUri;

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        return $this->client->getUserTweets(
            $this->screenName,
            [
                'count' => $perPage
            ]
        );
    }

    /**
     * @param object $item
     * @return array
     */
    protected function transform($item)
    {
        return [
            $item->text,
            "{$this->statusUri}{$this->screenName}/status/{$item->id_str}",
            Carbon::parse($item->created_at)->toDateTimeString(),
            null,
            null,
            null,
        ];
    }

    /**
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return $item->id_str;
    }

    /**
     * @param string $screenName
     */
    public function setScreenName($screenName)
    {
        $this->screenName = $screenName;
    }

    /**
     * @param string $statusUri
     */
    public function setStatusUri($statusUri)
    {
        $this->statusUri = $statusUri;
    }
}
