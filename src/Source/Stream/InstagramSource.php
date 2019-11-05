<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\InstagramClient;
use ArrayObject;
use Carbon\Carbon;

/**
 * @property InstagramClient $client
 */
class InstagramSource extends AbstractStreamSource
{
    public function __construct(
        PDOFactory $databaseFactory,
        FtpClient $ftpClient,
        InstagramClient $client
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'instagram';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage(): int
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    protected function extract($perPage, PageIterator $iterator): array
    {
        $iterator->setIsValid(false); // disable pagination in sandbox mode

        return $this->client->getMostRecentMedia()->data;
    }

    /**
     * {@inheritdoc}
     */
    protected function transform($item): ArrayObject
    {
        $title = null;

        if (!empty($item->caption)) {
            $title = $item->caption->text;
        } elseif (!empty($item->location)) {
            $title = $item->location->name;
        }

        if (empty($item->carousel_media)) {
            $images = [$item->images->low_resolution];
        } else {
            $images = array_map(
                function ($media) {
                    return $media->images->low_resolution;
                },
                $item->carousel_media
            );
        }

        $items = new ArrayObject();

        foreach ($images as $image) {
            $items[] = [
                $title,
                $item->link,
                Carbon::createFromTimestampUTC($item->created_time)->toDateTimeString(),
                $image->url,
                $image->width,
                $image->height,
            ];
        }

        return $items;
    }
}
