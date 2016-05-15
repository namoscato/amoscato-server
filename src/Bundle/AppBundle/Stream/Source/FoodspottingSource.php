<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;

class FoodspottingSource extends Source
{
    /** @var int */
    protected $perPage = 20;

    /** @var string */
    protected $type = 'foodspotting';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\FoodspottingClient */
    protected $client;

    /** @var string */
    private $personId;

    /** @var string */
    private $reviewUri;

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        return $this->client->getReviews(
            $this->personId,
            [
                'page' => $iterator->current(),
                'per_page' => $perPage,
                'sort' => 'latest'
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
            $item->thumb_280,
            280,
            280,
            "{$item->item->name} at {$item->place->name}",
            "{$this->reviewUri}{$item->id}"
        ];
    }

    /**
     * @param string $personId
     */
    public function setPersonId($personId)
    {
        $this->personId = $personId;
    }

    /**
     * @param string $reviewUri
     */
    public function setReviewUri($reviewUri)
    {
        $this->reviewUri = $reviewUri;
    }
}
