<?php

declare(strict_types=1);

namespace Amoscato\Source;

use Amoscato\Integration\Client\Client;

abstract class AbstractSource implements SourceInterface
{
    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
