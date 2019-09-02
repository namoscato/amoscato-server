<?php

declare(strict_types=1);

namespace Tests\Integration\Client\Middleware;

use Amoscato\Integration\Client\Middleware\StravaAuthentication;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class StravaAuthenticationTest extends MockeryTestCase
{
    /** @var StravaAuthentication */
    private $target;

    /** @var m\Mock */
    private $cache;

    /** @var MockHandler */
    private $mockHandler;

    /** @var array */
    private $requestHistory;

    protected function setUp(): void
    {
        $handlerStack = HandlerStack::create($this->mockHandler = new MockHandler());

        $this->requestHistory = [];

        $handlerStack->push(Middleware::history($this->requestHistory));

        $this->cache = m::mock(CacheInterface::class);

        $this->target = new StravaAuthentication(
            new Client(['handler' => $handlerStack]),
            $this->cache,
            'CLIENT',
            'SECRET',
            'REFRESH'
        );
    }

    public function testExpiredToken(): void
    {
        $this
            ->cache
            ->shouldReceive('get')
            ->once()
            ->with(
                StravaAuthentication::CACHE_ACCESS_TOKEN_KEY,
                m::type('callable')
            )
            ->andReturn('bar');

        $this
            ->cache
            ->shouldReceive('get')
            ->once()
            ->with(
                StravaAuthentication::CACHE_ACCESS_TOKEN_KEY,
                m::on(function (callable $callback) {
                    $item = m::mock(ItemInterface::class);

                    $this
                        ->mockHandler
                        ->append(new Response(
                            200,
                            [],
                            \GuzzleHttp\json_encode([
                                'access_token' => 'another bar',
                                'expires_in' => 3600,
                            ])
                        ));

                    $item
                        ->shouldReceive('expiresAfter')
                        ->once()
                        ->with(3600);

                    $this->assertEquals('another bar', $callback($item));

                    return true;
                })
            )
            ->andReturn('another bar');

        $count = 0;

        $nextHandler = function (RequestInterface $request) use (&$count) {
            $this->assertLessThanOrEqual(2, ++$count);

            if (1 === $count) {
                $this->assertEquals(['Bearer bar'], $request->getHeader('Authorization'));

                return new FulfilledPromise(new Response(401));
            }

            $this->assertEquals(['Bearer another bar'], $request->getHeader('Authorization'));

            return new FulfilledPromise(new Response());
        };

        /** @var PromiseInterface $response */
        $response = $this->target->__invoke($nextHandler)(new Request('GET', 'foo'), []);

        $this
            ->cache
            ->shouldReceive('delete')
            ->once()
            ->with(StravaAuthentication::CACHE_ACCESS_TOKEN_KEY);

        $response->wait();

        $this->assertCount(1, $this->requestHistory);

        /** @var RequestInterface $oauthRequest */
        $oauthRequest = $this->requestHistory[0]['request'];

        $this->assertEquals('POST', $oauthRequest->getMethod());

        parse_str($oauthRequest->getBody()->getContents(), $formParams);
        $this->assertEquals(
            [
                'client_id' => 'CLIENT',
                'client_secret' => 'SECRET',
                'grant_type' => 'refresh_token',
                'refresh_token' => 'REFRESH',
            ],
            $formParams
        );
    }

    public function testInvalidCredentials(): void
    {
        $this
            ->cache
            ->shouldReceive('get')
            ->andReturn('foo');

        $this
            ->cache
            ->shouldReceive('delete');

        $nextHandler = static function () {
            return new FulfilledPromise(new Response(401)); // ensure we don't get in an infinite loop
        };

        /** @var PromiseInterface $response */
        $response = $this->target->__invoke($nextHandler)(new Request('GET', 'foo'), []);
        $response->wait();
    }
}
