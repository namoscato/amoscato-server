<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;
use Symfony\Component\DomCrawler\Crawler;

class GoodreadsSource extends Source
{
    /** @var string */
    protected $type = 'goodreads';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\GoodreadsClient */
    protected $client;

    /** @var integer */
    private $userId;

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return \IteratorAggregate
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        return $this->client->getReadBooks(
            $this->userId,
            [
                'page' => $iterator->current(),
                'per_page' => $perPage
            ]
        );
    }

    /**
     * @param \DOMElement $item
     * @return array
     */
    protected function transform($item)
    {
        $crawler = $this->createCrawler($item);

        $imageUrl = $crawler->filter('image_url')->text();

        if (strpos($imageUrl, '/nophoto/')) { // Skip books with no photo
            return false;
        }

        $imageUrl = preg_replace( // Get reference to large image
            '/(^.+)(m)(\/\d+\.jpg)$/',
            '$1l$3',
            $imageUrl
        );

        $imageSize = $this->getImageSize($imageUrl);

        return [
            $imageUrl,
            $imageSize[0],
            $imageSize[1],
            $crawler->filter('title')->text(),
            $crawler->filter('link')->text()
        ];
    }

    /**
     * @param \DOMElement $item
     * @return string
     */
    protected function getSourceId($item)
    {
        $crawler = $this->createCrawler($item);

        return $crawler->filter('id')->text();
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param \DOMElement $node
     * @return Crawler
     */
    public function createCrawler($node)
    {
        return new Crawler($node);
    }

    /**
     * @param string $filename
     * @return array
     */
    public function getImageSize($filename)
    {
        return getimagesize($filename);
    }
}
