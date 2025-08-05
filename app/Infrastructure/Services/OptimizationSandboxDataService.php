<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\Contracts\Queries\Office\GetOfficeQuery;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStateCached;
use Carbon\CarbonInterface;

class OptimizationSandboxDataService
{
    public function __construct(
        private GetAllOfficesQuery $officesQuery,
        private PestRoutesOptimizationStateCached $optimizationStateResolver,
        private GetOfficeQuery $officeQuery,
    ) {
    }

    /**
     * Returns list of offices
     *
     * @return array<int, string>
     */
    public function getOffices(): array
    {
        return $this->officesQuery->get()
            ->mapWithKeys(function (Office $office) {
                return [$office->getId() => $office->getName()];
            })
            ->toArray();
    }

    /**
     * @param int $officeId
     * @param CarbonInterface $optimizationDate
     *
     * @return OptimizationState
     * @throws OfficeNotFoundException
     */
    public function getStateForOverview(int $officeId, CarbonInterface $optimizationDate): OptimizationState
    {
        $office = $this->officeQuery->get($officeId);

        return $this->optimizationStateResolver->resolve($optimizationDate, $office, new OptimizationParams());
    }
}
