<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\FlickrClient;
use Carbon\Carbon;

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
    public function getType(): string
    {
        return 'flickr';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage(): int
    {
        return 500;
    }

    /**
     * {@inheritdoc}
     */
    protected function extract($perPage, PageIterator $iterator): array
    {
        return $this->client->getPublicPhotos(
            $this->userId,
            [
                'extras' => 'url_m,path_alias,date_upload',
                'page' => $iterator->current(),
                'per_page' => $perPage,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function transform($item): array
    {
        return [
            $item->title,
            "{$this->photoUri}{$item->pathalias}/{$item->id}",
            Carbon::createFromTimestampUTC($item->dateupload)->toDateTimeString(),
            $item->url_m,
            $item->width_m,
            $item->height_m,
        ];
    }
}
