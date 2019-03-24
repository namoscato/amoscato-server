<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

class VimeoClient extends Client
{
    /**
     * @see https://developer.vimeo.com/api/endpoints/me#/likes
     *
     * @param array $args optional
     *
     * @return object
     */
    public function getLikes(array $args = [])
    {
        $response = $this->client->get(
            'me/likes',
            [
                'headers' => [
                    'Authorization' => "bearer {$this->apiKey}",
                ],
                'query' => array_merge(
                    $args,
                    [
                        'sort' => 'date',
                    ]
                ),
            ]
        );

        return \GuzzleHttp\json_decode($response->getBody());
    }
}
