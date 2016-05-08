<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Query\PhotoStatementProvider;
use Amoscato\Bundle\IntegrationBundle\Client\FlickrClient;
use PDO;

class FlickrSource extends Source
{
    /**
     * @var string
     */
    protected $type = 'flickr';

    /**
     * @var PhotoStatementProvider
     */
    private $statementProvider;

    /**
     * @var FlickrClient
     */
    private $client;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $photoPath;

    /**
     * @param PDO $database
     * @param FlickrClient $client
     */
    public function __construct(PDO $database, FlickrClient $client)
    {
        $this->statementProvider = new PhotoStatementProvider($database);
        $this->client = $client;
    }

    public function load()
    {
        $photos = $this->client->getPublicPhotos(
            $this->userId,
            [
                'extras' => 'url_m,path_alias'
            ]
        );

        $count = 0;
        $values = [];

        foreach ($photos as $photo) {
            array_push(
                $values,
                $this->type,
                $photo->id,
                $photo->url_m,
                $photo->width_m,
                $photo->height_m,
                $photo->title,
                "{$this->photoPath}{$photo->pathalias}/{$photo->id}"
            );
            $count++;
        }

        $statement = $this->statementProvider->insertRows($count);

        $statement->execute($values);
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setPhotoPath($photoPath)
    {
        $this->photoPath = $photoPath;
    }
}
