<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\ValueObjects\TreatmentStateIdentity;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;

class TreatmentStateFactory extends AbstractFactory
{
    public function single($overrides = []): TreatmentState
    {
        return new TreatmentState(
            id: new TreatmentStateIdentity(
                $overrides['officeId'] ?? $this->faker->randomNumber(6),
                $overrides['date'] ?? Carbon::today(),
            ),
            servicedRoutes: $overrides['servicedRoutes'] ?? collect(),
            trackingData: $overrides['trackingData'] ?? collect(),
            drivingStats: $overrides['drivingStats'] ?? collect()
        );
    }
}
