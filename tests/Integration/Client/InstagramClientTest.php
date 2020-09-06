<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\InstagramClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\RequestInterface;

class InstagramClientTest extends MockeryTestCase
{
    /** @var InstagramClient */
    private $target;

    /** @var array */
    private $requestHistory;

    protected function setUp(): void
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], \GuzzleHttp\json_encode(['foo' => 'bar'])),
        ]));

        $this->requestHistory = [];

        $stack->push(Middleware::history($this->requestHistory));

        $this->target = new InstagramClient(new Client(['handler' => $stack]), 'key');
    }

    public function testGetMostRecentMedia(): void
    {
        self::assertEquals(
            (object) ['foo' => 'bar'],
            $this->target->getMostRecentMedia(['page' => 1])
        );

        self::assertCount(1, $this->requestHistory);

        /** @var RequestInterface $request */
        $request = $this->requestHistory[0]['request'];

        self::assertEquals('GET', $request->getMethod());

        self::assertEquals('users/self/media/recent', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);

        self::assertEquals(
            [
                'page' => 1,
                'access_token' => 'key',
            ],
            $query
        );
    }
}
