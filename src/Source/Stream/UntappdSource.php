<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\UntappdClient;
use Carbon\Carbon;

/**
 * @property UntappdClient $client
 */
class UntappdSource extends AbstractStreamSource
{
    /** @var string */
    private $username;

    /**
     * @param string $username
     */
    public function __construct(
        PDOFactory $databaseFactory,
        UntappdClient $client,
        $username,
    ) {
        parent::__construct($databaseFactory, $client);

        $this->username = $username;
    }

    public function getType(): string
    {
        return 'untappd';
    }

    protected function getMaxPerPage(): int
    {
        return 50;
    }

    protected function extract($perPage, PageIterator $iterator): array
    {
        $response = $this->client->getUserBadges(
            $this->username,
            [
                'offset' => $iterator->current() - 1,
                'limit' => $perPage,
            ]
        );

        $iterator->setNextPageValue($perPage * $iterator->key() + 1);

        return $response->items;
    }

    protected function transform($item): array
    {
        return [
            $item->badge_name,
            $this->client->getBadgeUrl($this->username, $item->user_badge_id),
            Carbon::parse($item->created_at)->toDateTimeString(),
            $item->media->badge_image_lg,
            400,
            400,
        ];
    }

    protected function getSourceId($item): string
    {
        return (string) $item->user_badge_id;
    }
}
