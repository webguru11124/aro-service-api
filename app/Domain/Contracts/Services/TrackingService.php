<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\Exceptions\FailedPublishTrackingDataException;

interface TrackingService
{
    /**
     * Publishes tracking data
     *
     * @param TreatmentState $state
     *
     * @return void
     * @throws FailedPublishTrackingDataException
     */
    public function publish(TreatmentState $state): void;
}
