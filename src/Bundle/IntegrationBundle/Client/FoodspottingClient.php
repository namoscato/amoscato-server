<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class FoodspottingClient extends Client
{
    /**
     * @see http://www.foodspotting.com/api#people-reviews
     * @param string $personId
     * @param array $args optional
     * @return array
     */
    public function getReviews($personId, $args = [])
    {
        $response = $this->client->get(
            "people/{$personId}/reviews.json",
            [
                'query' => array_merge(
                    $args,
                    [
                        'api_key' => $this->apiKey
                    ]
                )
            ]
        );

        $body = json_decode($response->getBody());

        return $body->data->reviews;
    }
}
