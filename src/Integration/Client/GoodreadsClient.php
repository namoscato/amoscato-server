<?php

declare(strict_types=1);

namespace Amoscato\Integration\Client;

use Symfony\Component\DomCrawler\Crawler;

class GoodreadsClient extends Client
{
    /**
     * @return Crawler
     */
    public function getCurrentlyReadingBooks($userId, array $args = []): iterable
    {
        return $this->getBooks('currently-reading', $userId, $args);
    }

    /**
     * @param int $userId
     * @param array $args optional
     *
     * @return Crawler
     */
    public function getReadBooks($userId, array $args = []): iterable
    {
        return $this->getBooks('read', $userId, $args);
    }

    /**
     * @param string $node
     */
    public function createCrawler($node): Crawler
    {
        return new Crawler($node);
    }

    /**
     * @see https://www.goodreads.com/api/index#reviews.list
     *
     * @param string $shelf
     * @param int $userId
     *
     * @return Crawler
     */
    private function getBooks($shelf, $userId, array $args = []): iterable
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
                    ]
                ),
            ]
        );

        $crawler = $this->createCrawler((string) $response->getBody());

        return $crawler->filter('GoodreadsResponse reviews review');
    }
}
