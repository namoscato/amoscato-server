<?php

declare(strict_types=1);

namespace Amoscato\Source\Current;

use Amoscato\Integration\Client\UntappdClient;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class DrinkSource implements CurrentSourceInterface
{
    /** @var UntappdClient */
    protected $client;

    /** @var string */
    private $username;

    /**
     * @param string $username
     */
    public function __construct(UntappdClient $client, $username)
    {
        $this->client = $client;
        $this->username = $username;
    }

    public function getType(): string
    {
        return 'drink';
    }

    public function load(OutputInterface $output): array
    {
        $response = $this
            ->client
            ->getUserCheckins(
                $this->username,
                [
                    'limit' => 1,
                ]
            );

        $checkin = $response->checkins->items[0];

        return [
            'brewery' => $checkin->brewery->brewery_name,
            'date' => Carbon::parse($checkin->created_at)->toDateTimeString(),
            'name' => $checkin->beer->beer_name,
            'venue' => $checkin->venue->venue_name ?? null,
            'url' => $this->client->getCheckinUrl($this->username, $checkin->checkin_id),
        ];
    }
}
