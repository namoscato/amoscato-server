<?php

namespace Amoscato\Client;

class ClientFactory implements ClientFactoryInterface
{
    /**
     * @var string
     */
    protected $client;

    /**
     * @param string $client
     */
    public function __construct($client = 'Amoscato\\Client\\Client')
    {
        $this->client = $client;
    }

    /**
     * @param array $config optional
     * @return \GuzzleHttp\ClientInterface
     */
    public function getClient(array $config = [])
    {
        return new $this->client($config);
    }
}
