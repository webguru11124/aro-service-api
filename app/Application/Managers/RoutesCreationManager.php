<?php

declare(strict_types=1);

namespace App\Application\Managers;

use App\Application\DTO\RouteCreationDTO;
use App\Application\Jobs\RoutesCreationJob;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Queries\Office\OfficesByIdsQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates/manages the process of routes creation
 */
class RoutesCreationManager
{
    private const ROUTE_CREATION_ENABLED_FOR_OFFICE_FEATURE_FLAG = 'isRouteCreationForOfficeEnabled';

    /** @var Collection<Office> */
    private Collection $offices;
    private RouteCreationDTO $routeCreationData;

    public function __construct(
        private readonly OfficesByIdsQuery $officesByIdsQuery,
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Runs route creation process for given offices and dates
     *
     * @param RouteCreationDTO $routeCreationData
     *
     * @return void
     * @throws OfficeNotFoundException
     */
    public function manage(RouteCreationDTO $routeCreationData): void
    {
        $this->routeCreationData = $routeCreationData;

        $this->resolveOfficesEnabledForRouteCreation();
        $this->processRoutesCreation();
    }

    /**
     * Resolves offices enabled for route creation
     *
     * @return void
     * @throws OfficeNotFoundException
     */
    private function resolveOfficesEnabledForRouteCreation(): void
    {
        $this->offices = $this->officesByIdsQuery
            ->get(...$this->routeCreationData->officeIds)
            ->filter(function (Office $office) {
                if (!$this->isRouteCreationForOfficeEnabled($office)) {
                    Log::notice(__('messages.route_creation.is_disabled', [
                        'office' => $office->getName(),
                        'office_id' => $office->getId(),
                    ]));

                    return false;
                }

                return true;
            });
    }

    private function isRouteCreationForOfficeEnabled(Office $office): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $office->getId(),
            self::ROUTE_CREATION_ENABLED_FOR_OFFICE_FEATURE_FLAG
        );
    }

    private function processRoutesCreation(): void
    {
        if (empty($this->routeCreationData->numDaysToCreateRoutes)) {
            return;
        }

        foreach ($this->offices as $office) {
            $creationDate = $this->resolveRoutesCreationDateForOffice($office);
            $numDaysToCreateRoutes = $this->routeCreationData->numDaysToCreateRoutes;

            while ($numDaysToCreateRoutes > 0) {
                $this->processRoutesCreationForOffice($office, $creationDate);
                $creationDate->addDay();
                $numDaysToCreateRoutes--;
            }
        }
    }

    private function resolveRoutesCreationDateForOffice(Office $office): CarbonInterface
    {
        if (!empty($this->routeCreationData->startDate)) {
            return Carbon::parse(
                $this->routeCreationData->startDate->toFormattedDateString(),
                $office->getTimeZone(),
            );
        }

        $date = Carbon::today($office->getTimeZone());

        if (!empty($this->routeCreationData->numDaysAfterStartDate)) {
            $date->addDays($this->routeCreationData->numDaysAfterStartDate);
        }

        return $date;
    }

    private function processRoutesCreationForOffice(Office $office, CarbonInterface $date): void
    {
        RoutesCreationJob::dispatch(
            $date,
            $office,
        );

        $this->logRoutesCreationInitiated($office, $date);
    }

    private function logRoutesCreationInitiated(Office $office, CarbonInterface $date): void
    {
        Log::info(__('messages.route_creation.initiated', [
            'office' => $office->getName(),
            'office_id' => $office->getId(),
            'date' => $date->toFormattedDateString(),
        ]));
    }
}
