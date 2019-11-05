<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Amoscato\Ftp\FtpClient;
use Amoscato\Integration\Client\TwitterClient;
use Carbon\Carbon;

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
    public function getType(): string
    {
        return 'twitter';
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
    protected function extract($perPage, PageIterator $iterator): array
    {
        return $this->client->getUserTweets(
            $this->screenName,
            [
                'count' => $perPage,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function transform($item)
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
     * {@inheritdoc}
     */
    protected function getSourceId($item): string
    {
        return $item->id_str;
    }
}
