<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

use GuzzleHttp\Exception\ClientException;

class VimeoClient extends Client
{
    /**
     * @see https://developer.vimeo.com/api/endpoints/me#/likes
     * @param array $args optional
     * @return object
     */
    public function getLikes($args = [])
    {
        $response = $this->client->get(
            'me/likes',
            [
                'headers' => [
                    'Authorization' => "bearer {$this->apiKey}"
                ],
                'query' => array_merge(
                    $args,
                    [
                        'sort' => 'date'
                    ]
                )
            ]
        );

        return json_decode($response->getBody());
    }
}
