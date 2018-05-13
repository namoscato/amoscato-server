<?php

namespace Amoscato\Bundle\AppBundle\Source;

use Amoscato\Bundle\IntegrationBundle\Client\Client;

abstract class AbstractSource implements SourceInterface
{
    /** @var Client */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
