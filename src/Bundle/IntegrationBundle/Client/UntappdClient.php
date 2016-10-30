<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class UntappdClient extends Client
{
    /** @var string */
    private $clientId;

    /**
     * @param string $username
     * @param int $checkinId
     * @return string
     */
    public function getCheckinUrl($username, $checkinId)
    {
        return "https://untappd.com/user/{$username}/checkin/{$checkinId}";
    }

    /**
     * @see https://untappd.com/api/docs#useractivityfeed
     * @param string $username
     * @param array $args optional
     * @return object
     */
    public function getUserCheckins($username, array $args = [])
    {
        $response = $this->client->get(
            "user/checkins/{$username}",
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

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }
}
