<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class LastfmClient extends Client
{
    const METHOD_ALBUM_GET_INFO = 'album.getInfo';
    const METHOD_USER_GET_RECENT_TRACKS = 'user.getRecentTracks';

    /**
     * @see http://www.last.fm/api/show/album.getInfo
     * @param string $id
     * @param array $args optional
     * @return object
     */
    public function getAlbumInfoById($id, array $args = [])
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
     * @see http://www.last.fm/api/show/album.getInfo
     * @param string $artistName
     * @param string $albumName
     * @param array $args optional
     * @return mixed
     */
    public function getAlbumInfoByName($artistName, $albumName, array $args = [])
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
    public function getRecentTracks($user, array $args = [])
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
     * @see http://www.last.fm/api/show/user.getRecentTracks
     * @param string $method
     * @param array $args optional
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function get($method, array $args = [])
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
