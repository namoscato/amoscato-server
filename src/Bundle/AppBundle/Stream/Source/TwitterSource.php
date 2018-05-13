<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\IntegrationBundle\Client\TwitterClient;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property TwitterClient $client
 */
class TwitterSource extends AbstractStreamSource
{
    /** @var string */
    private $screenName;

    /** @var string */
    private $statusUri;

    /**
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param TwitterClient $client
     * @param string $screenName
     * @param string $statusUri
     */
    public function __construct(
        PDOFactory $databaseFactory,
        FtpClient $ftpClient,
        TwitterClient $client,
        $screenName,
        $statusUri
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);

        $this->screenName = $screenName;
        $this->statusUri = $statusUri;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'twitter';
    }

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        return $this->client->getUserTweets(
            $this->screenName,
            [
                'count' => $perPage
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
            $item->text,
            "{$this->statusUri}{$this->screenName}/status/{$item->id_str}",
            Carbon::parse($item->created_at)->toDateTimeString(),
            null,
            null,
            null,
        ];
    }

    /**
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return $item->id_str;
    }
}
