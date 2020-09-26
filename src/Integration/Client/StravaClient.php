<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use Amoscato\Integration\Client\Middleware\StravaAuthentication;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Utils;

class StravaClient
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @return StravaClient
     */
    public static function create(string $baseUri, StravaAuthentication $authentication): self
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->push($authentication, 'authentication');

        return new self(new GuzzleClient([
            'base_uri' => $baseUri,
            'handler' => $handlerStack,
        ]));
    }

    public function __construct(GuzzleClient $client)
    {
        $this->client = $client;
    }

    /**
     * @see http://developers.strava.com/docs/reference/#api-Activities-getLoggedInAthleteActivities
     *
     * @param array $args optional
     *
     * @return object
     */
    public function getActivities(array $args = [])
    {
        return $this->get('api/v3/athlete/activities', $args);
    }

    /**
     * @param string $uri
     *
     * @return object
     */
    private function get($uri, array $args = [])
    {
        $response = $this->client->get(
            $uri,
            ['query' => $args]
        );

        return Utils::jsonDecode((string) $response->getBody());
    }
}
