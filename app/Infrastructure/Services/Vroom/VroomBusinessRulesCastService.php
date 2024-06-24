<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom;

use App\Application\Events\OptimizationRuleApplied;
use App\Domain\Contracts\OptimizationRule;
use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeed;
use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeedForUnderutilizedRoutes;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Infrastructure\Services\Vroom\BusinessRuleCasters\BusinessRuleCaster;
use App\Infrastructure\Services\Vroom\BusinessRuleCasters\TravelSpeedCaster;
use App\Infrastructure\Services\Vroom\BusinessRuleCasters\TravelSpeedForUnderutilizedRoutesCaster;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use Illuminate\Support\Collection;

class VroomBusinessRulesCastService
{
    public function castRules(
        VroomInputData $inputData,
        OptimizationState $optimizationState,
        Collection $rules,
    ): VroomInputData {
        foreach ($rules as $rule) {
            $caster = $this->matchCaster($rule);
            if ($caster === null) {
                continue;
            }
            OptimizationRuleApplied::dispatch($rule);
            $inputData = $caster->cast($inputData, $optimizationState, $rule);
        }

        return $inputData;
    }

    private function matchCaster(OptimizationRule $rule): BusinessRuleCaster|null
    {
        return match (get_class($rule)) {
            IncreaseTravelSpeed::class => app(TravelSpeedCaster::class),
            IncreaseTravelSpeedForUnderutilizedRoutes::class => app(TravelSpeedForUnderutilizedRoutesCaster::class),
            default => null,
        };
    }
}
