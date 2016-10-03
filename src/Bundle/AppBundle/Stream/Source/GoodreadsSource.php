<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class GoodreadsSource extends AbstractSource
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
        $review = $this->createCrawler($item);
        $book = $review->filter('book');

        $imageUrl = $book->filter('image_url')->text();
        $imageWidth = null;
        $imageHeight = null;

        if (strpos($imageUrl, '/nophoto/')) {
            $imageUrl = null;
        } else {
            $imageUrl = preg_replace( // Get reference to large image
                '/(^.+)(m)(\/\d+\.jpg)$/',
                '$1l$3',
                $imageUrl
            );

            $imageSize = $this->getImageSize($imageUrl);

            $imageWidth = $imageSize[0];
            $imageHeight = $imageSize[1];
        }

        $readAt = $review->filter('read_at')->text();

        if (empty($readAt)) {
            $readAt = $review->filter('date_added')->text();
        }

        return [
            $book->filter('title')->text(),
            $book->filter('link')->text(),
            Carbon::parse($readAt)->setTimezone('UTC')->toDateTimeString(),
            $imageUrl,
            $imageWidth,
            $imageHeight
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
