<?php

declare(strict_types=1);

namespace App\Application\Managers;

use App\Application\DTO\RouteOptimizationDTO;
use App\Application\Events\OptimizationSkipped;
use App\Application\Jobs\OptimizeRoutesJob;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Queries\Office\OfficesByIdsQuery;
use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\InvalidOptimizationTimeException;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates/manages the process of optimizing routes
 */
class RoutesOptimizationManager
{
    private const OPTIMIZATION_ENABLED_ON_DAY_BEFORE_SERVICE_FEATURE_FLAG = 'isDayBeforeServiceOptimizationEnabled';
    private const OPTIMIZATION_ENABLED_FOR_OFFICE_FEATURE_FLAG = 'isRouteOptimizationForOfficeEnabled';

    /** @var Collection<Office> */
    private Collection $offices;
    private RouteOptimizationDTO $optimizeRoutesData;

    public function __construct(
        private readonly OfficesByIdsQuery $officesByIdsQuery,
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Runs optimization process for given offices and dates
     *
     * @param RouteOptimizationDTO $optimizeRoutesData
     *
     * @return void
     * @throws OfficeNotFoundException
     */
    public function manage(RouteOptimizationDTO $optimizeRoutesData): void
    {
        $this->optimizeRoutesData = $optimizeRoutesData;

        $this->resolveOfficesEnabledForOptimization();
        $this->runOptimization();
    }

    private function resolveOfficesEnabledForOptimization(): void
    {
        $this->offices = $this->officesByIdsQuery
            ->get(...$this->optimizeRoutesData->officeIds)
            ->filter(function (Office $office) {
                if (!$this->isRouteOptimizationForOfficeEnabled($office)) {
                    Log::notice(__('messages.office.optimization_is_disabled', [
                        'office' => $office->getName(),
                        'office_id' => $office->getId(),
                    ]));

                    return false;
                }

                return true;
            });
    }

    private function isRouteOptimizationForOfficeEnabled(Office $office): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $office->getId(),
            self::OPTIMIZATION_ENABLED_FOR_OFFICE_FEATURE_FLAG
        );
    }

    private function runOptimization(): void
    {
        foreach ($this->offices as $office) {
            $optimizationDate = $this->resolveOptimizationDateForOffice($office);

            if (!$this->isValidOptimizationTime($optimizationDate)) {
                Log::notice(__('messages.office.not_allowed_to_optimize_after_time', [
                    'office' => $office->getName(),
                    'office_id' => $office->getId(),
                    'time' => DomainContext::getAllowRouteOptimizationBeforeTime(),
                ]));
                OptimizationSkipped::dispatch($office, $optimizationDate, new InvalidOptimizationTimeException());

                continue;
            }

            $this->runOptimizationForOffice($office, $optimizationDate);
        }

        if (empty($this->optimizeRoutesData->numDaysToOptimize)) {
            return;
        }

        foreach ($this->offices as $office) {
            $optimizationDate = $this->resolveOptimizationDateForOffice($office);
            $numDaysToOptimize = $this->optimizeRoutesData->numDaysToOptimize;

            while ($numDaysToOptimize > 0) {
                $optimizationDate->addDay();
                $this->runOptimizationForOffice($office, $optimizationDate);
                $numDaysToOptimize--;
            }
        }
    }

    private function resolveOptimizationDateForOffice(Office $office): CarbonInterface
    {
        if (!empty($this->optimizeRoutesData->startDate)) {
            return Carbon::parse(
                $this->optimizeRoutesData->startDate->toFormattedDateString(),
                $office->getTimeZone()
            );
        }

        $date = Carbon::today($office->getTimeZone());

        if (!empty($this->optimizeRoutesData->numDaysAfterStartDate)) {
            $date->addDays($this->optimizeRoutesData->numDaysAfterStartDate);
        }

        return $date;
    }

    private function runOptimizationForOffice(Office $office, CarbonInterface $date): void
    {
        $logParams = [
            'office' => $office->getName(),
            'office_id' => $office->getId(),
            'date' => $date->toDateString(),
        ];

        if ($this->isTodayOrDayBeforeService($date) && !$this->isDayBeforeBeforeServiceOptimizationEnabled($office)) {
            Log::notice(__('messages.office.not_allowed_to_optimize_before_service', $logParams));

            return;
        }

        OptimizeRoutesJob::dispatch(
            $date,
            $office,
            new OptimizationParams(
                $this->optimizeRoutesData->lastOptimizationRun,
                $this->optimizeRoutesData->simulationRun,
                $this->optimizeRoutesData->buildPlannedOptimization,
                $this->optimizeRoutesData->disabledRules,
            )
        );
        Log::info(__('messages.office.optimization_initiated', $logParams));
    }

    private function isTodayOrDayBeforeService(CarbonInterface $date): bool
    {
        $tz = $date->getTimezone();

        return $date->isSameDay(Carbon::tomorrow($tz)) || $date->isSameDay(Carbon::now($tz));
    }

    private function isValidOptimizationTime(CarbonInterface $date): bool
    {
        $today = Carbon::today($date->getTimeZone());

        if (config('app.debug') || !$date->isSameDay($today)) { // this allows skip validation on testing environment
            return true;
        }

        return $this->isBeforeAllowedOptimizationTime($date);
    }

    private function isBeforeAllowedOptimizationTime(CarbonInterface $date): bool
    {
        $currentTimeInOffice = Carbon::now($date->getTimeZone());
        $cutoffTime = $currentTimeInOffice->clone()->setTimeFromTimeString(DomainContext::getAllowRouteOptimizationBeforeTime());

        return $currentTimeInOffice->lessThanOrEqualTo($cutoffTime);
    }

    private function isDayBeforeBeforeServiceOptimizationEnabled(Office $office): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $office->getId(),
            self::OPTIMIZATION_ENABLED_ON_DAY_BEFORE_SERVICE_FEATURE_FLAG
        );
    }
}
