<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class FlickrClient extends Client
{
    const METHOD_PEOPLE_GET_PUBLIC_PHOTOS = 'flickr.people.getPublicPhotos';

    /**
     * @see https://www.flickr.com/services/api/flickr.people.getPublicPhotos.html
     * @param string $userId
     * @param array $args optional
     * @return array
     */
    public function getPublicPhotos($userId, array $args = [])
    {
        $args['user_id'] = $userId;

        $response = $this->get(
            self::METHOD_PEOPLE_GET_PUBLIC_PHOTOS,
            $args
        );

        $body = json_decode($response->getBody());

        return $body->photos->photo;
    }

    /**
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
                        'method' => $method,
                        'nojsoncallback' => 1
                    ]
                )
            ]
        );
    }
}
