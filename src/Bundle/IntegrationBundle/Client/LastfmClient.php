<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class LastfmClient extends Client
{
    const METHOD_ALBUM_GET_INFO = 'album.getInfo';
    const METHOD_USER_GET_RECENT_TRACKS = 'user.getRecentTracks';

    /**
     * @param string $id
     * @param array $args optional
     * @return object
     */
    public function getAlbumInfoById($id, $args = [])
    {
        $args['mbid'] = $id;

        $response = $this->get(
            self::METHOD_ALBUM_GET_INFO,
            $args
        );

        $body = json_decode($response->getBody());

        return $body->album;
    }

    /**
     * @param string $artistName
     * @param string $albumName
     * @param array $args optional
     * @return mixed
     */
    public function getAlbumInfoByName($artistName, $albumName, $args = [])
    {
        $args['artist'] = $artistName;
        $args['album'] = $albumName;

        $response = $this->get(
            self::METHOD_ALBUM_GET_INFO,
            $args
        );

        $body = json_decode($response->getBody());

        return $body->album;
    }

    /**
     * @param string $user
     * @param array $args optional
     * @return array
     */
    public function getRecentTracks($user, $args = [])
    {
        $args['user'] = $user;

        $response = $this->get(
            self::METHOD_USER_GET_RECENT_TRACKS,
            $args
        );

        $body = json_decode($response->getBody());

        return $body->recenttracks->track;
    }

    /**
     * @param string $method
     * @param array $args optional
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function get($method, $args = [])
    {
        return $this->client->get(
            '',
            [
                'query' => array_merge(
                    $args,
                    [
                        'api_key' => $this->apiKey,
                        'format' => 'json',
                        'method' => $method
                    ]
                )
            ]
        );
    }
}
