<?php

namespace Amoscato\Integration\Client;

class YouTubeClient extends Client
{
    /**
     * @see https://developers.google.com/youtube/v3/docs/playlistItems/list
     * @param string $playlistId
     * @param array $args optional
     * @return object
     */
    public function getPlaylistItems($playlistId, array $args = [])
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
