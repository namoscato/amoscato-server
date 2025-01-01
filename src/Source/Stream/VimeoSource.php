<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\VimeoClient;
use Carbon\Carbon;

/**
 * @property VimeoClient $client
 */
class VimeoSource extends AbstractStreamSource
{
    public function __construct(
        PDOFactory $databaseFactory,
        VimeoClient $client
    ) {
        parent::__construct($databaseFactory, $client);
    }

    public function getType(): string
    {
        return 'vimeo';
    }

    protected function getMaxPerPage(): int
    {
        return 50;
    }

    protected function extract($perPage, PageIterator $iterator): array
    {
        $response = $this->client->getLikes(
            [
                'page' => $iterator->current(),
                'per_page' => $perPage,
            ]
        );

        if (!isset($response->paging->next)) {
            $iterator->setIsValid(false);
        }

        return $response->data;
    }

    protected function transform($item): array
    {
        $image = $item->pictures->sizes[2];

        return [
            $item->name,
            $item->link,
            Carbon::parse($item->metadata->interactions->like->added_time)->toDateTimeString(),
            $image->link,
            $image->width,
            $image->height,
        ];
    }

    protected function getSourceId($item): string
    {
        return substr($item->uri, 8); // Remove "/videos/" prefix
    }
}
