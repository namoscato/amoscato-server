<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;
use Carbon\Carbon;

class VimeoSource extends AbstractSource
{
    /** @var int */
    protected $perPage = 50;

    /** @var string */
    protected $type = 'vimeo';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\VimeoClient */
    protected $client;

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        $response = $this->client->getLikes(
            [
                'page' => $iterator->current(),
                'per_page' => $perPage
            ]
        );

        if (!isset($response->paging->next)) {
            $iterator->setIsValid(false);
        }
        
        return $response->data;
    }

    /**
     * @param object $item
     * @return array
     */
    protected function transform($item)
    {
        $image = $item->pictures->sizes[2];

        return [
            $item->name,
            $item->link,
            Carbon::parse($item->metadata->interactions->like->added_time)->toDateTimeString(),
            $image->link,
            $image->width,
            $image->height
        ];
    }

    /**
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return substr($item->uri, 8); // Remove "/videos/" prefix
    }
}
