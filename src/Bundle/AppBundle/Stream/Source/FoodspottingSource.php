<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

class FoodspottingSource extends Source
{
    const LIMIT = 20;

    /**
     * @var string
     */
    protected $type = 'foodspotting';

    /**
     * @var \Amoscato\Bundle\IntegrationBundle\Client\FoodspottingClient
     */
    protected $client;

    /**
     * @var string
     */
    private $personId;

    /**
     * @var string
     */
    private $reviewUri;

    /**
     * @param int $limit
     * @param int $page
     * @return array
     */
    protected function extract($limit = self::LIMIT, $page = 1)
    {
        return $this->client->getReviews(
            $this->personId,
            [
                'page' => $page,
                'per_page' => $limit,
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
