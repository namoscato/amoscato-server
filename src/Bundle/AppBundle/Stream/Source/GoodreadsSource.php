<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\IntegrationBundle\Client\GoodreadsClient;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property GoodreadsClient $client
 */
class GoodreadsSource extends AbstractStreamSource
{
    /** @var integer */
    private $userId;

    /**
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param GoodreadsClient $client
     * @param string $userId
     */
    public function __construct(
        PDOFactory $databaseFactory,
        FtpClient $ftpClient,
        GoodreadsClient $client,
        $userId
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);

        $this->userId = $userId;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'goodreads';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage()
    {
        return 200;
    }

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
                'per_page' => $perPage,
                'sort' => 'date_read'
            ]
        );
    }

    /**
     * @param \DOMElement $item
     * @param OutputInterface $output
     * @return array
     */
    protected function transform($item, OutputInterface $output)
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

            list($imageWidth, $imageHeight) = $this->getImageSize($imageUrl);
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
