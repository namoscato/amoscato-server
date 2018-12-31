<?php

namespace Amoscato\Integration\Client;

class TwitterClient extends Client
{
    /**
     * @see https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-user_timeline
     * @param string $screenName
     * @param array $args optional
     * @return array
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
