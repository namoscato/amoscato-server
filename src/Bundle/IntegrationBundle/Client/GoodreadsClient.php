<?php

namespace Amoscato\Bundle\IntegrationBundle\Client;

use Symfony\Component\DomCrawler\Crawler;

class GoodreadsClient extends Client
{
    /**
     * @param $userId
     * @param array $args
     * @return Crawler
     */
    public function getCurrentlyReadingBooks($userId, array $args = [])
    {
        return $this->getBooks('currently-reading', $userId, $args);
    }

    /**
     * @param integer $userId
     * @param array $args optional
     * @return Crawler
     */
    public function getReadBooks($userId, array $args = [])
    {
        return $this->getBooks('read', $userId, $args);
    }

    /**
     * @param string $node
     * @return Crawler
     */
    public function createCrawler($node)
    {
        return new Crawler($node);
    }

    /**
     * @see https://www.goodreads.com/api/index#reviews.list
     * @param string $shelf
     * @param int $userId
     * @param array $args
     * @return Crawler
     */
    private function getBooks($shelf, $userId, array $args = [])
    {
        $response = $this->client->get(
            "review/list/{$userId}.xml",
            [
                'query' => array_merge(
                    $args,
                    [
                        'key' => $this->apiKey,
                        'v' => 2,
                        'shelf' => $shelf,
                        'sort' => 'date_read'
                    ]
                )
            ]
        );

        $crawler = $this->createCrawler((string) $response->getBody());

        return $crawler->filter('GoodreadsResponse reviews review');
    }
}
