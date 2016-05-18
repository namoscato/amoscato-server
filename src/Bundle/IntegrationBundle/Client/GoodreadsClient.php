<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

use Symfony\Component\DomCrawler\Crawler;

class GoodreadsClient extends Client
{
    /**
     * @see https://www.goodreads.com/api/index#reviews.list
     * @param integer $userId
     * @param array $args optional
     * @return Crawler
     */
    public function getReadBooks($userId, array $args = [])
    {
        $response = $this->client->get(
            "review/list/{$userId}.xml",
            [
                'query' => array_merge(
                    $args,
                    [
                        'key' => $this->apiKey,
                        'v' => 2,
                        'shelf' => 'read',
                        'sort' => 'date_read'
                    ]
                )
            ]
        );

        $crawler = $this->createCrawler((string) $response->getBody());

        return $crawler->filter('GoodreadsResponse reviews review');
    }

    /**
     * @param string $node
     * @return Crawler
     */
    public function createCrawler($node)
    {
        return new Crawler($node);
    }
}
