<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

class FlickrSource extends Source
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
     * @param int $page optional
     * @return array
     */
    protected function extract($perPage, $page = 1)
    {
        return $this->client->getPublicPhotos(
            $this->userId,
            [
                'extras' => 'url_m,path_alias',
                'page' => $page,
                'per_page' => $perPage
            ]
        );
    }

    /**
     * @param object $item
     * @return array
     */
    protected function transform($item)
    {
        return [
            $item->url_m,
            $item->width_m,
            $item->height_m,
            $item->title,
            "{$this->photoUri}{$item->pathalias}/{$item->id}"
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
