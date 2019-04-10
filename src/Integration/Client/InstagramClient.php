<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

class InstagramClient extends Client
{
    /**
     * @see https://www.instagram.com/developer/endpoints/users/#get_users_media_recent_self
     *
     * @param array $query
     *
     * @return object
     */
    public function getMostRecentMedia(array $query = [])
    {
        return $this->get('users/self/media/recent', $query);
    }

    /**
     * @param string $uri
     * @param array $query
     *
     * @return object
     */
    private function get($uri, array $query = [])
    {
        $query['access_token'] = $this->apiKey;

        return \GuzzleHttp\json_decode($this->client->get($uri, ['query' => $query])->getBody());
    }
}