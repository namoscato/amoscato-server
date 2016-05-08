<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

use GuzzleHttp\Client;

class FlickrClient
{
    const METHOD_PEOPLE_GET_PUBLIC_PHOTOS = 'flickr.people.getPublicPhotos';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     * @param string $apiKey
     */
    public function __construct(Client $client, $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $userId
     * @param array $args optional
     * @return array
     */
    public function getPublicPhotos($userId, $args = [])
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
                        'method' => $method,
                        'nojsoncallback' => 1
                    ]
                )
            ]
        );
    }
}
