<?php

namespace Amoscato\Source\Stream;

use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\UntappdClient;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property UntappdClient $client
 */
class UntappdSource extends AbstractStreamSource
{
    /** @var string */
    private $username;

    /**
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param UntappdClient $client
     * @param string $username
     */
    public function __construct(
        PDOFactory $databaseFactory,
        FtpClient $ftpClient,
        UntappdClient $client,
        $username
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);

        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'untappd';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage()
    {
        return 50;
    }

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        $response = $this->client->getUserBadges(
            $this->username,
            [
                'offset' => $iterator->current() - 1,
                'limit' => $perPage
            ]
        );

        $iterator->setNextPageValue($perPage * $iterator->key() + 1);

        return $response->items;
    }

    /**
     * @param object $item
     * @param OutputInterface $output
     * @return array
     */
    protected function transform($item, OutputInterface $output)
    {
        return [
            $item->badge_name,
            $this->client->getBadgeUrl($this->username, $item->user_badge_id),
            Carbon::parse($item->created_at)->toDateTimeString(),
            $item->media->badge_image_lg,
            400,
            400
        ];
    }

    /**
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return (string) $item->user_badge_id;
    }
}
