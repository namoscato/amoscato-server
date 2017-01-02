<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class UntappdSource extends AbstractSource
{
    /** @var int */
    protected $perPage = 50;

    /** @var string */
    protected $type = 'untappd';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\UntappdClient */
    protected $client;

    /** @var string */
    private $username;

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        $response = $this->client->getUserBadges(
            $this->username,
            [
                'offset' => $iterator->current() - 1,
                'limit' => $perPage
            ]
        );

        $iterator->setNextPageValue($perPage * $iterator->key() + 1);

        return $response->items;
    }

    /**
     * @param object $item
     * @param OutputInterface $output
     * @return array
     */
    protected function transform($item, OutputInterface $output)
    {
        return [
            $item->badge_name,
            $this->client->getBadgeUrl($this->username, $item->user_badge_id),
            Carbon::parse($item->created_at)->toDateTimeString(),
            $item->media->badge_image_lg,
            400,
            400
        ];
    }

    /**
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return (string) $item->user_badge_id;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
}
