<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\BusinessRuleCasters;

use App\Domain\RouteOptimization\BusinessRules\BusinessRule;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;

interface BusinessRuleCaster
{
    /**
     * @param VroomInputData $inputData
     * @param OptimizationState $optimizationState
     * @param BusinessRule $rule
     *
     * @return VroomInputData
     */
    public function cast(
        VroomInputData $inputData,
        OptimizationState $optimizationState,
        BusinessRule $rule
    ): VroomInputData;
}
