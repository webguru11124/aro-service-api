<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoAppointmentsFoundException;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Exceptions\RoutesHaveNoCapacityException;
use Carbon\CarbonInterface;

interface OptimizationStateResolver
{
    /**
     * Resolves OptimizationState data
     *
     * @param CarbonInterface $date
     * @param Office $office
     * @param OptimizationParams $optimizationParams
     *
     * @return OptimizationState
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     * @throws NoAppointmentsFoundException
     * @throws RoutesHaveNoCapacityException
     */
    public function resolve(
        CarbonInterface $date,
        Office $office,
        OptimizationParams $optimizationParams
    ): OptimizationState;
}
