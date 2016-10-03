<?php

namespace Amoscato\Bundle\AppBundle\Source;

use Amoscato\Bundle\IntegrationBundle\Client\Client;

abstract class AbstractSource implements SourceInterface
{
    /** @var Client */
    protected $client;

    /** @var string */
    protected $type;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
