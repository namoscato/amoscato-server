<?php

declare(strict_types=1);

namespace Amoscato\Source\Current;

use Amoscato\Integration\Client\GoodreadsClient;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class BookSource implements CurrentSourceInterface
{
    /** @var GoodreadsClient */
    protected $client;

    /** @var string */
    private $userId;

    /**
     * @param string $userId
     */
    public function __construct(GoodreadsClient $client, $userId)
    {
        $this->client = $client;
        $this->userId = $userId;
    }

    public function getType(): string
    {
        return 'book';
    }

    public function load(OutputInterface $output): ?array
    {
        $reviews = $this
            ->client
            ->getCurrentlyReadingBooks(
                $this->userId,
                [
                    'per_page' => 1,
                ]
            );

        if (0 === $reviews->count()) {
            return null;
        }

        $review = $reviews->first();

        $startedAt = $review->filter('started_at')->text();

        if (empty($startedAt)) {
            $startedAt = $review->filter('date_added')->text();
        }

        $book = $review->filter('book');

        return [
            'author' => $book->filter('authors')->first()->filter('name')->text(),
            'date' => Carbon::parse($startedAt)->setTimezone('UTC')->toDateTimeString(),
            'title' => $book->filter('title')->text(),
            'url' => $book->filter('link')->text(),
        ];
    }
}
