<?php

namespace Amoscato\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Source\AbstractSource;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class BookSource extends AbstractSource
{
    /** @var string */
    protected $type = 'book';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\GoodreadsClient */
    protected $client;

    /** @var string */
    private $userId;

    /**
     * @param OutputInterface $output
     * @return array
     */
    public function load(OutputInterface $output)
    {
        $reviews = $this
            ->client
            ->getCurrentlyReadingBooks(
                $this->userId,
                [
                    'per_page' => 1
                ]
            );

        if ($reviews->count() === 0) {
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
            'url' => $book->filter('link')->text()
        ];
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
}
