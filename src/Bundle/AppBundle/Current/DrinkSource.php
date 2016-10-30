<?php

namespace Amoscato\Bundle\AppBundle\Current;

use Amoscato\Bundle\AppBundle\Source\AbstractSource;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class DrinkSource extends AbstractSource
{
    /** @var string */
    protected $type = 'drink';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\UntappdClient */
    protected $client;

    /** @var string */
    private $username;

    /**
     * @param OutputInterface $output
     * @return array
     */
    public function load(OutputInterface $output)
    {
        $response = $this
            ->client
            ->getUserCheckins(
                $this->username,
                [
                    'limit' => 1
                ]
            );

        $checkin = $response->checkins->items[0];

        return [
            'brewery' => $checkin->brewery->brewery_name,
            'date' => Carbon::parse($checkin->created_at)->toDateTimeString(),
            'name' => $checkin->beer->beer_name,
            'venue' => $checkin->venue->venue_name,
            'url' => $this->client->getCheckinUrl($this->username, $checkin->checkin_id)
        ];
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
}
