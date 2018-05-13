<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

class GitHubClient extends Client
{
    const EVENT_TYPE_PUSH = 'PushEvent';
    const MAX_EVENT_PAGES = 10;

    /** @var string */
    private $clientId;

    /**
     * @see https://developer.github.com/v3/activity/events/#list-events-performed-by-a-user
     * @param string $username
     * @param array $args optional
     * @return array
     */
    public function getUserEvents($username, array $args = [])
    {
        return $this->get(
            "users/{$username}/events",
            $args
        );
    }

    /**
     * @see https://developer.github.com/v3/git/commits/#get-a-commit
     * @param string $commitUrl
     * @return object
     */
    public function getCommit($commitUrl)
    {
        return $this->get($commitUrl);
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
     * @param array $args optional
     * @return mixed
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

        return json_decode($response->getBody());
    }
}
