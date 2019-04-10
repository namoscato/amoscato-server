<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use Amoscato\Integration\Client\Middleware\StravaAuthentication;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;

class StravaClient
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @param string $baseUri
     * @param StravaAuthentication $authentication
     *
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

    /**
     * @param GuzzleClient $client
     */
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
     * @param array $args
     *
     * @return object
     */
    private function get($uri, array $args = [])
    {
        $response = $this->client->get(
            $uri,
            ['query' => $args]
        );

        return \GuzzleHttp\json_decode($response->getBody());
    }
}
