<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\BusinessRuleCasters;

use App\Domain\RouteOptimization\BusinessRules\BusinessRule;
use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeedForUnderutilizedRoutes;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Infrastructure\Services\Vroom\DTO\Vehicle;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;

class TravelSpeedForUnderutilizedRoutesCaster extends AbstractBusinessRuleCaster
{
    /**
     * @param VroomInputData $inputData
     * @param OptimizationState $optimizationState
     * @param IncreaseTravelSpeedForUnderutilizedRoutes $rule
     *
     * @return VroomInputData
     */
    public function cast(
        VroomInputData $inputData,
        OptimizationState $optimizationState,
        BusinessRule $rule
    ): VroomInputData {
        $serviceProIds = $this->getServiceProIdsWithUnderutilizedCapacity(
            $optimizationState,
            $rule->getUnderUtilizationPercent()
        );

        /** @var Vehicle $vehicle */
        foreach ($inputData->getVehicles() as $vehicle) {
            $servicePro = $this->findServiceProByVehicle($optimizationState, $vehicle);
            if ($servicePro === null || !in_array($servicePro->getId(), $serviceProIds)) {
                continue;
            }

            $vehicle->setSpeedFactor($vehicle->getSpeedFactor() + $rule->getSpeedFactorIncreaseValue());
        }

        return $inputData;
    }

    /**
     * @param OptimizationState $optimizationState
     * @param int $underUtilizationPercent
     *
     * @return array<int>
     */
    private function getServiceProIdsWithUnderutilizedCapacity(
        OptimizationState $optimizationState,
        int $underUtilizationPercent
    ): array {
        $serviceProIds = [];

        /** @var Route $route */
        foreach ($optimizationState->getRoutes() as $route) {
            $serviceProCapacity = $route->getCapacity();

            if (!empty($serviceProCapacity)) {
                $utilizationPercent = ceil($route->getAppointments()->count() / $serviceProCapacity * 100.0);

                if ($utilizationPercent < $underUtilizationPercent) {
                    $serviceProIds[] = $route->getServicePro()->getId();
                }
            }
        }

        return $serviceProIds;
    }
}
