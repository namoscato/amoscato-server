<?php

namespace Amoscato\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Source\AbstractSource;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class FoodSource extends AbstractSource
{
    /** @var string */
    protected $type = 'food';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\FoodspottingClient */
    protected $client;

    /** @var string */
    private $personId;

    /** @var string */
    private $reviewUri;

    /**
     * @param OutputInterface $output
     * @return array
     */
    public function load(OutputInterface $output)
    {
        $result = $this->client->getReviews(
            $this->personId,
            [
                'per_page' => 1,
                'sort' => 'latest'
            ]
        );

        $review = $result[0];

        return [
            'item' => $review->item->name,
            'place' => $review->place->name,
            'date' => Carbon::parse($review->taken_at)->toDateTimeString(),
            'url' => "{$this->reviewUri}{$review->id}"
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
