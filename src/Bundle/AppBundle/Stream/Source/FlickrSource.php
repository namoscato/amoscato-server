<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\IntegrationBundle\Client\FlickrClient;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property FlickrClient $client
 */
class FlickrSource extends AbstractStreamSource
{
    /** @var string */
    private $userId;

    /** @var string */
    private $photoUri;

    /**
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param FlickrClient $client
     * @param string $userId
     * @param string $photoUri
     */
    public function __construct(
        PDOFactory $databaseFactory,
        FtpClient $ftpClient,
        FlickrClient $client,
        $userId,
        $photoUri
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);

        $this->userId = $userId;
        $this->photoUri = $photoUri;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'flickr';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage()
    {
        return 500;
    }

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        return $this->client->getPublicPhotos(
            $this->userId,
            [
                'extras' => 'url_m,path_alias,date_upload',
                'page' => $iterator->current(),
                'per_page' => $perPage
            ]
        );
    }

    /**
     * @param object $item
     * @param OutputInterface $output
     * @return array
     */
    protected function transform($item, OutputInterface $output)
    {
        return [
            $item->title,
            "{$this->photoUri}{$item->pathalias}/{$item->id}",
            Carbon::createFromTimestampUTC($item->dateupload)->toDateTimeString(),
            $item->url_m,
            $item->width_m,
            $item->height_m
        ];
    }
}
