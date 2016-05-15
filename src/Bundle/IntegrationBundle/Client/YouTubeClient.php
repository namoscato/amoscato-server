<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class YouTubeClient extends Client
{
    /**
     * @param string $playlistId
     * @param array $args optional
     * @return object
     */
    public function getPlaylistItems($playlistId, $args = [])
    {
        $response = $this->client->get(
            'playlistItems',
            [
                'query' => array_merge(
                    $args,
                    [
                        'key' => $this->apiKey,
                        'part' => 'snippet',
                        'playlistId' => $playlistId
                    ]
                )
            ]
        );

        return json_decode($response->getBody());
    }
}
