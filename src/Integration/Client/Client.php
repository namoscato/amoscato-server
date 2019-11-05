<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use GuzzleHttp\Client as GuzzleClient;

abstract class Client
{
    /** @var string */
    protected $apiKey;

    /** @var Client */
    protected $client;

    /**
     * @param string $apiKey
     */
    public function __construct(GuzzleClient $client, $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }
}
