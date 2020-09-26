<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use GuzzleHttp\Utils;

class YouTubeClient extends Client
{
    /**
     * @see https://developers.google.com/youtube/v3/docs/playlistItems/list
     *
     * @param string $playlistId
     * @param array $args optional
     *
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
                        'playlistId' => $playlistId,
                    ]
                ),
            ]
        );

        return Utils::jsonDecode((string) $response->getBody());
    }
}
