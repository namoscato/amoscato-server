<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

class StravaClient extends Client
{
    /**
     * @see http://developers.strava.com/docs/reference/#api-Activities-getLoggedInAthleteActivities
     *
     * @param array $args optional
     *
     * @return object
     */
    public function getActivities(array $args = [])
    {
        return $this->get('athlete/activities', $args);
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
                'headers' => ['Authorization' => "Bearer {$this->apiKey}"],
                'query' => $args,
            ]
        );

        return \GuzzleHttp\json_decode($response->getBody());
    }
}
