<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class UntappdClient extends Client
{
    /** @var string */
    private $clientId;

    /**
     * @param string $username
     * @param int $userBadgeId
     * @return string
     */
    public function getBadgeUrl($username, $userBadgeId)
    {
        return $this->getUserUrl($username, "badges/{$userBadgeId}");
    }

    /**
     * @param string $username
     * @param int $checkinId
     * @return string
     */
    public function getCheckinUrl($username, $checkinId)
    {
        return $this->getUserUrl($username, "checkin/{$checkinId}");
    }

    /**
     * @param string $username
     * @param string $path
     * @return string
     */
    public function getUserUrl($username, $path = '')
    {
        return "https://untappd.com/user/{$username}/{$path}";
    }

    /**
     * @see https://untappd.com/api/docs#userbadges
     * @param string $username
     * @param array $args
     * @return object
     */
    public function getUserBadges($username, array $args = [])
    {
        return $this->get("user/badges/{$username}", $args);
    }

    /**
     * @see https://untappd.com/api/docs#useractivityfeed
     * @param string $username
     * @param array $args optional
     * @return object
     */
    public function getUserCheckins($username, array $args = [])
    {
        return $this->get("user/checkins/{$username}", $args);
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @param string $uri
     * @param array $args
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
                        'client_secret' => $this->apiKey
                    ]
                )
            ]
        );

        return json_decode($response->getBody())->response;
    }
}
