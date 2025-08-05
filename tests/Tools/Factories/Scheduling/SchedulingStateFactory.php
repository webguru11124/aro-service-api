<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Scheduling;

use App\Domain\Scheduling\Entities\SchedulingState;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\Factories\OfficeFactory;

class SchedulingStateFactory extends AbstractFactory
{
    public function single($overrides = []): SchedulingState
    {
        $schedulingState = new SchedulingState(
            $overrides['id'] ?? $this->faker->randomNumber(6),
            $overrides['date'] ?? Carbon::today(),
            $overrides['office'] ?? OfficeFactory::make(),
        );

        $schedulingState->addScheduledRoutes($overrides['scheduledRoutes'] ?? collect());
        $schedulingState->addPendingServices($overrides['pendingServices'] ?? collect());

        if (!empty($overrides['allActiveServiceProIds'])) {
            $schedulingState->setAllActiveEmployeeIds($overrides['allActiveServiceProIds']);
        }

        return $schedulingState;
    }
}
