<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\GoodreadsClient;
use Carbon\Carbon;
use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property GoodreadsClient $client
 */
class GoodreadsSource extends AbstractStreamSource
{
    /** @var int */
    private $userId;

    /**
     * @param string $userId
     */
    public function __construct(
        PDOFactory $databaseFactory,
        GoodreadsClient $client,
        $userId
    ) {
        parent::__construct($databaseFactory, $client);

        $this->userId = $userId;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'goodreads';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage(): int
    {
        return 200;
    }

    /**
     * {@inheritdoc}
     */
    protected function extract($perPage, PageIterator $iterator): iterable
    {
        return $this->client->getReadBooks(
            $this->userId,
            [
                'page' => $iterator->current(),
                'per_page' => $perPage,
                'sort' => 'date_read',
            ]
        );
    }

    /**
     * @param DOMElement $item
     */
    protected function transform($item): array
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

            [$imageWidth, $imageHeight] = $this->getImageSize($imageUrl);
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
            $imageHeight,
        ];
    }

    /**
     * @param DOMElement $item
     */
    protected function getSourceId($item): string
    {
        $crawler = $this->createCrawler($item);

        return $crawler->filter('id')->text();
    }

    /**
     * @param DOMElement $node
     */
    public function createCrawler($node): Crawler
    {
        return new Crawler($node);
    }

    /**
     * @param string $filename
     */
    public function getImageSize($filename): array
    {
        return getimagesize($filename);
    }
}
