<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\BusinessRuleCasters;

use App\Domain\RouteOptimization\BusinessRules\BusinessRule;
use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeed;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Infrastructure\Services\Vroom\DTO\Vehicle;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;

class TravelSpeedCaster extends AbstractBusinessRuleCaster
{
    /**
     * @param VroomInputData $inputData
     * @param OptimizationState $optimizationState
     * @param IncreaseTravelSpeed $rule
     *
     * @return VroomInputData
     */
    public function cast(
        VroomInputData $inputData,
        OptimizationState $optimizationState,
        BusinessRule $rule
    ): VroomInputData {
        /** @var Vehicle $vehicle */
        foreach ($inputData->getVehicles() as $vehicle) {
            $servicePro = $this->findServiceProByVehicle($optimizationState, $vehicle);
            if ($servicePro === null) {
                continue;
            }

            $vehicle->setSpeedFactor($vehicle->getSpeedFactor() + $rule->getSpeedFactorIncreaseValue());
        }

        return $inputData;
    }
}
