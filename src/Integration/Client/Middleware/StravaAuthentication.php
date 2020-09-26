<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client\Middleware;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Utils;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @see http://developers.strava.com/docs/authentication/
 */
class StravaAuthentication
{
    /** @var string Cache key that temporarily stores Strava access token */
    public const CACHE_ACCESS_TOKEN_KEY = 'strava.accessToken';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    public function __construct(
        Client $client,
        CacheInterface $cache,
        string $clientId,
        string $clientSecret,
        string $refreshToken
    ) {
        $this->client = $client;
        $this->cache = $cache;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
    }

    public function __invoke(callable $nextHandler): Closure
    {
        return function (RequestInterface $request, array $options) use ($nextHandler) {
            return $this->addAuthorizationHeader($nextHandler, $request, $options);
        };
    }

    /**
     * Adds the authorization header to the request, refreshing it if it is invalid.
     *
     * @throws InvalidArgumentException
     */
    private function addAuthorizationHeader(callable $nextHandler, RequestInterface $request, array $options): PromiseInterface
    {
        if (empty($options['refresh_access_token'])) {
            $options['refresh_access_token'] = false;
        }

        /** @var PromiseInterface $promise */
        $promise = $nextHandler(
            $request->withHeader('Authorization', sprintf('Bearer %s', $this->getAccessToken($options['refresh_access_token']))),
            $options
        );

        return $promise->then(function (ResponseInterface $response) use ($nextHandler, $request, $options) {
            if ($options['refresh_access_token'] || Response::HTTP_UNAUTHORIZED !== $response->getStatusCode()) {
                return $response;
            }

            $options['refresh_access_token'] = true;

            return $this->addAuthorizationHeader($nextHandler, $request, $options);
        });
    }

    /**
     * Returns the access token used for authorization, refreshed from cache if it is expired.
     *
     * @param bool $refresh force refreshes the access token
     *
     * @throws InvalidArgumentException
     */
    private function getAccessToken(bool $refresh = false): string
    {
        if ($refresh) {
            $this->cache->delete(self::CACHE_ACCESS_TOKEN_KEY);
        }

        return $this->cache->get(self::CACHE_ACCESS_TOKEN_KEY, function (ItemInterface $item) {
            $response = $this->client->post(
                'oauth/token',
                [
                    'form_params' => [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $this->refreshToken,
                    ],
                ]
            );

            $response = Utils::jsonDecode((string) $response->getBody(), true);

            $item->expiresAfter($response['expires_in']);

            return $response['access_token'];
        });
    }
}
