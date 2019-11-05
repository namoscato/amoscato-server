<?php

declare(strict_types=1);

namespace Amoscato\Source\Current;

use Amoscato\Integration\Client\StravaClient;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

class AthleticActivitySource implements CurrentSourceInterface
{
    /** @var StravaClient */
    private $stravaClient;

    /** @var string */
    private $activityUri;

    /**
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
    public function getType(): string
    {
        return 'athleticActivity';
    }

    /**
     * {@inheritdoc}
     */
    public function load(OutputInterface $output): array
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
