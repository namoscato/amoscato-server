<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use GuzzleHttp\Client as GuzzleClient;

class UntappdClient extends Client
{
    /** @var string */
    private $clientId;

    /**
     * @param GuzzleClient $client
     * @param string $apiKey
     * @param string $clientId
     */
    public function __construct(GuzzleClient $client, $apiKey, $clientId)
    {
        parent::__construct($client, $apiKey);

        $this->clientId = $clientId;
    }

    /**
     * @param string $username
     * @param int $userBadgeId
     *
     * @return string
     */
    public function getBadgeUrl($username, $userBadgeId): string
    {
        return $this->getUserUrl($username, "badges/{$userBadgeId}");
    }

    /**
     * @param string $username
     * @param int $checkinId
     *
     * @return string
     */
    public function getCheckinUrl($username, $checkinId): string
    {
        return $this->getUserUrl($username, "checkin/{$checkinId}");
    }

    /**
     * @param string $username
     * @param string $path
     *
     * @return string
     */
    public function getUserUrl($username, $path = ''): string
    {
        return "https://untappd.com/user/{$username}/{$path}";
    }

    /**
     * @see https://untappd.com/api/docs#userbadges
     *
     * @param string $username
     * @param array $args
     *
     * @return object
     */
    public function getUserBadges($username, array $args = [])
    {
        return $this->get("user/badges/{$username}", $args);
    }

    /**
     * @see https://untappd.com/api/docs#useractivityfeed
     *
     * @param string $username
     * @param array $args optional
     *
     * @return object
     */
    public function getUserCheckins($username, array $args = [])
    {
        return $this->get("user/checkins/{$username}", $args);
    }

    /**
     * @param string $uri
     * @param array $args
     *
     * @return object
     */
    private function get($uri, array $args = [])
    {
        $response = $this->client->get(
            $uri,
            [
                'query' => array_merge(
                    $args,
                    [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->apiKey,
                    ]
                ),
            ]
        );

        return \GuzzleHttp\json_decode($response->getBody())->response;
    }
}
