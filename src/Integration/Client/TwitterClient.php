<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use GuzzleHttp\Utils;

class TwitterClient extends Client
{
    /**
     * @see https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-user_timeline
     *
     * @param string $screenName
     * @param array $args optional
     */
    public function getUserTweets($screenName, array $args = []): array
    {
        $response = $this->client->get(
            'statuses/user_timeline.json',
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
                'query' => array_merge(
                    $args,
                    [
                        'contributor_details' => false,
                        'screen_name' => $screenName,
                    ]
                ),
            ]
        );

        return Utils::jsonDecode((string) $response->getBody());
    }
}
