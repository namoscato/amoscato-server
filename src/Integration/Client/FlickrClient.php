<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use Psr\Http\Message\ResponseInterface;

class FlickrClient extends Client
{
    private const METHOD_PEOPLE_GET_PUBLIC_PHOTOS = 'flickr.people.getPublicPhotos';

    /**
     * @see https://www.flickr.com/services/api/flickr.people.getPublicPhotos.html
     *
     * @param string $userId
     * @param array $args optional
     */
    public function getPublicPhotos($userId, array $args = []): array
    {
        $args['user_id'] = $userId;

        $response = $this->get(
            self::METHOD_PEOPLE_GET_PUBLIC_PHOTOS,
            $args
        );

        $body = \GuzzleHttp\json_decode((string) $response->getBody());

        return $body->photos->photo;
    }

    /**
     * @param string $method
     * @param array $args optional
     */
    private function get($method, array $args = []): ResponseInterface
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
                        'nojsoncallback' => 1,
                    ]
                ),
            ]
        );
    }
}
