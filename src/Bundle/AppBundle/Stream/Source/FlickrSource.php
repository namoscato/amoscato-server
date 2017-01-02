<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Console\Helper\PageIterator;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class FlickrSource extends AbstractSource
{
    /** @var string */
    protected $type = 'flickr';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\FlickrClient */
    protected $client;

    /** @var string */
    private $userId;

    /** @var string */
    private $photoUri;

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

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param string $photoUri
     */
    public function setPhotoUri($photoUri)
    {
        $this->photoUri = $photoUri;
    }
}
