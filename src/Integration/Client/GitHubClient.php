<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use GuzzleHttp\Client as GuzzleClient;

class GitHubClient extends Client
{
    public const EVENT_TYPE_PUSH = 'PushEvent';
    public const MAX_EVENT_PAGES = 10;

    /** @var string */
    private $clientId;

    /**
     * @param string $apiKey
     * @param string $clientId
     */
    public function __construct(GuzzleClient $client, $apiKey, $clientId)
    {
        parent::__construct($client, $apiKey);

        $this->clientId = $clientId;
    }

    /**
     * @see https://developer.github.com/v3/activity/events/#list-events-performed-by-a-user
     *
     * @param string $username
     * @param array $args optional
     */
    public function getUserEvents($username, array $args = []): array
    {
        return $this->get(
            "users/{$username}/events",
            $args
        );
    }

    /**
     * @see https://developer.github.com/v3/git/commits/#get-a-commit
     *
     * @param string $commitUrl
     *
     * @return object
     */
    public function getCommit($commitUrl)
    {
        return $this->get($commitUrl);
    }

    /**
     * @param string $uri
     * @param array $args optional
     *
     * @return mixed
     */
    private function get($uri, array $args = [])
    {
        $response = $this->client->get(
            $uri,
            [
                'auth' => [$this->clientId, $this->apiKey],
                'query' => $args,
            ]
        );

        return \GuzzleHttp\json_decode($response->getBody());
    }
}
