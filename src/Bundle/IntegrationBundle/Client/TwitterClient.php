<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class TwitterClient extends Client
{
    /**
     * @see https://dev.twitter.com/rest/reference/get/statuses/user_timeline
     * @param string $screenName
     * @param array $args optional
     * @return object
     */
    public function getUserTweets($screenName, array $args = [])
    {
        $response = $this->client->get(
            'statuses/user_timeline.json',
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}"
                ],
                'query' => array_merge(
                    $args,
                    [
                        'contributor_details' => false,
                        'screen_name' => $screenName
                    ]
                )
            ]
        );

        return json_decode($response->getBody());
    }
}
