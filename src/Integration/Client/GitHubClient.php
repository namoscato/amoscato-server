<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Utils;

class GitHubClient extends Client
{
    public const EVENT_TYPE_PUSH = 'PushEvent';

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
     * @see https://docs.github.com/en/rest/activity/events#list-events-for-the-authenticated-user
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
     * @see https://docs.github.com/en/rest/commits/commits#compare-two-commits
     *
     * @param string $owner
     * @param string $repo
     * @param string $basehead Two commit SHAs in the format "base...head"
     *
     * @return object
     */
    public function compareCommits($owner, $repo, $basehead)
    {
        return $this->get("repos/{$owner}/{$repo}/compare/{$basehead}");
    }

    /**
     * @param string $uri
     * @param array $args optional
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

        return Utils::jsonDecode((string) $response->getBody());
    }
}
