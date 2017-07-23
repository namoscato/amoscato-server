<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class FoodspottingSource extends AbstractSource
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
     * @param OutputInterface $output
     * @return array
     */
    protected function transform($item, OutputInterface $output)
    {
        return [
            "{$item->item->name} at {$item->place->name}",
            "{$this->reviewUri}{$item->id}",
            Carbon::parse($item->taken_at)->toDateTimeString(),
            $this->cachePhoto($output, $item->thumb_280),
            280,
            280
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
