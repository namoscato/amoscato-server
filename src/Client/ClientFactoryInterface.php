<?php

namespace Amoscato\Client;

interface ClientFactoryInterface
{
    /**
     * @param array $config optional
     * @return \GuzzleHttp\ClientInterface
     */
    public function getClient(array $config = []);
}
