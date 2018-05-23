<?php

namespace Amoscato\Bundle\AppBundle\Current;

use Amoscato\Bundle\IntegrationBundle\Client\StravaClient;
use Amoscato\Console\Output\ConsoleOutput;
use Carbon\Carbon;

class AthleticActivitySource implements CurrentSourceInterface
{
    /** @var StravaClient */
    private $stravaClient;

    /** @var string */
    private $activityUri;

    /**
     * @param StravaClient $stravaClient
     * @param string $activityUri
     */
    public function __construct(StravaClient $stravaClient, $activityUri)
    {
        $this->stravaClient = $stravaClient;
        $this->activityUri = $activityUri;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'athleticActivity';
    }

    /**
     * {@inheritdoc}
     */
    public function load(ConsoleOutput $output, $limit = 1)
    {
        $activity = $this->stravaClient->getActivities(['per_page' => 1])[0];

        return [
            'date' => Carbon::parse($activity->start_date)->toDateTimeString(),
            'miles' => 0.000621371 * $activity->distance,
            'minutes' => $activity->moving_time / 60,
            'type' => $activity->type,
            'url' => "{$this->activityUri}{$activity->id}",
        ];
    }
}
