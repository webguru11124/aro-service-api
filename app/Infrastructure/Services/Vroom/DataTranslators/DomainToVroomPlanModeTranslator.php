<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Infrastructure\Services\Vroom\DTO\Vehicle;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Infrastructure\Services\Vroom\DTO\VehicleStep;
use App\Infrastructure\Services\Vroom\DTO\VroomEngineOption;
use App\Infrastructure\Services\Vroom\Enums\StepType;
use Illuminate\Support\Collection;

class DomainToVroomPlanModeTranslator extends DomainToVroomTranslator
{
    protected function getEngineOptions(): Collection
    {
        return new Collection([VroomEngineOption::GEOMETRY, VroomEngineOption::CHOOSE_ETA]);
    }

    protected function vehicleFromRoute(Route $route): Vehicle
    {
        $vehicle = $this->buildVehicle($route);
        $this->addStepsToVehicle($route, $vehicle);

        return $vehicle;
    }

    private function addStepsToVehicle(Route $route, Vehicle $vehicle): void
    {
        $workEvents = $route->getWorkEvents();

        /** @var Appointment|WorkBreak $workEvent */
        foreach ($workEvents as $workEvent) {
            $stepType = match (true) {
                $workEvent instanceof Appointment => StepType::JOB,
                $workEvent instanceof WorkBreak, $workEvent instanceof ReservedTime => StepType::BREAK,
                default => null
            };

            if ($stepType) {
                if (
                    $stepType == StepType::BREAK
                    && !$route->getTimeWindow()->isDateInTimeWindow($workEvent->getExpectedArrival()->getStartAt())
                ) {
                    continue;
                }

                $vehicle->addStep(new VehicleStep(
                    type: $stepType,
                    id: $workEvent->getId(),
                ));
            }
        }
    }
}
