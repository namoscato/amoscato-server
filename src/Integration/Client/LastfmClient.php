<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use Amoscato\Integration\Exception\LastfmBadResponseException;

class LastfmClient extends Client
{
    private const METHOD_ALBUM_GET_INFO = 'album.getInfo';
    private const METHOD_USER_GET_RECENT_TRACKS = 'user.getRecentTracks';

    /**
     * @see http://www.last.fm/api/show/album.getInfo
     *
     * @param string $id
     * @param array $args optional
     *
     * @return object
     */
    public function getAlbumInfoById($id, array $args = [])
    {
        $args['mbid'] = $id;

        return $this->get(self::METHOD_ALBUM_GET_INFO, $args)->album;
    }

    /**
     * @see http://www.last.fm/api/show/album.getInfo
     *
     * @param string $artistName
     * @param string $albumName
     * @param array $args optional
     *
     * @return mixed
     */
    public function getAlbumInfoByName($artistName, $albumName, array $args = [])
    {
        $args['artist'] = $artistName;
        $args['album'] = $albumName;

        $body = $this->get(self::METHOD_ALBUM_GET_INFO, $args);

        return $body->album ?? $body;
    }

    /**
     * @see http://www.last.fm/api/show/user.getRecentTracks
     *
     * @param string $user
     * @param array $args optional
     */
    public function getRecentTracks($user, array $args = []): array
    {
        $args['user'] = $user;

        return $this->get(self::METHOD_USER_GET_RECENT_TRACKS, $args)->recenttracks->track;
    }

    /**
     * @param string $method
     * @param array $args optional
     *
     * @return object
     */
    private function get($method, array $args = [])
    {
        $response = $this->client->get(
            '',
            [
                'query' => array_merge(
                    $args,
                    [
                        'api_key' => $this->apiKey,
                        'format' => 'json',
                        'method' => $method,
                    ]
                ),
            ]
        );

        $responseBody = \GuzzleHttp\json_decode($response->getBody());

        if (!empty($responseBody->error)) {
            throw new LastfmBadResponseException($responseBody);
        }

        return $responseBody;
    }
}
