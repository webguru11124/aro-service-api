<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes;

use App\Domain\Contracts\OptimizationStateResolver;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;

class PestRoutesOptimizationStateCached implements OptimizationStateResolver
{
    private const HASH_ALGO = 'md5';
    private const CACHE_TTL = 60; //in minutes

    public function __construct(
        private readonly PestRoutesOptimizationStateResolver $pestRoutesOptimizationStateResolver,
    ) {
    }

    /**
     * Returns OptimizationState based on data received from PestRoutes
     *
     * @param CarbonInterface $date
     * @param Office $office
     * @param OptimizationParams $optimizationParams
     *
     * @return OptimizationState
     */
    public function resolve(
        CarbonInterface $date,
        Office $office,
        OptimizationParams $optimizationParams,
    ): OptimizationState {

        $cacheKey = 'pre_' . $this->buildKey([
            'date' => $date->toDateString(),
            'office' => $office->getId(),
            'optimizationParams' => $optimizationParams,
        ]);

        return cache()->remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($date, $office, $optimizationParams) {
            return $this->pestRoutesOptimizationStateResolver->resolve($date, $office, $optimizationParams);
        });
    }

    /**
     * @param array<string, mixed> $methodArguments
     *
     * @return string
     */
    private function buildKey(array $methodArguments): string
    {
        return class_basename($this) . '_' . hash(self::HASH_ALGO, serialize($methodArguments));
    }
}
