<?php

declare(strict_types=1);

namespace Tests\Integration\Client;

use Amoscato\Integration\Client\StravaClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\RequestInterface;

class StravaClientTest extends MockeryTestCase
{
    /** @var StravaClient */
    private $target;

    private $requestHistory;

    protected function setUp(): void
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], Utils::jsonEncode(['foo' => 'bar'])),
        ]));

        $this->requestHistory = [];
        $stack->push(Middleware::history($this->requestHistory));

        $this->target = new StravaClient(new Client(['handler' => $stack]));
    }

    public function test_getActivities(): void
    {
        self::assertEquals(
            (object) ['foo' => 'bar'],
            $this->target->getActivities(['page' => 1])
        );

        self::assertCount(1, $this->requestHistory);

        /** @var RequestInterface $request */
        $request = $this->requestHistory[0]['request'];

        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('api/v3/athlete/activities', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        self::assertEquals(['page' => 1], $query);
    }
}
