<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive;

use App\Domain\Contracts\Services\RouteDrivingStatsService;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\DriverUtilization;
use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\Params\SearchDriverUtilizationParams;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriod;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodStatus;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodType;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\Params\SearchDrivingPeriodsParams;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\Params\SearchVehicleMileageParams;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\VehicleMileage;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;

class MotiveRouteDrivingDataService extends AbstractMotiveDataService implements RouteDrivingStatsService
{
    private const CACHE_DRIVING_PERIODS_TTL = 86400;
    private const CACHE_DRIVER_UTILIZATION_TTL = 86400;
    private const CACHE_VEHICLE_MILEAGE_TTL = 86400;
    private const HISTORIC_DATA_DURATION_WEEKS = 2;

    /** @var array<int, int[]> */
    private array $driverVehicleMap = [];
    private bool $forceUpdateCache = false;
    private CarbonInterface $date;
    /** @var Collection<int, Collection<DrivingPeriod>>  */
    private Collection $preloadedDrivingPeriods;
    /** @var Collection<int, Collection<DriverUtilization>> */
    private Collection $preloadedDriverUtilization;
    /** @var Collection<int, Collection<VehicleMileage>> */
    private Collection $preloadedVehicleMileage;
    /** @var Collection<int, Collection<DriverUtilization>>  */
    private Collection $preloadedHistoricalDriverUtilization;

    /**
     * @param string[] $userIds
     * @param CarbonInterface $date
     *
     * @return Collection<RouteDrivingStats>
     * @throws InvalidArgumentException
     */
    public function get(array $userIds, CarbonInterface $date): Collection
    {
        $this->date = $date;
        $todayInOffice = Carbon::today($date->getTimezone());
        $this->forceUpdateCache = $date->isSameDay($todayInOffice);

        try {
            $mapDriverIds = $this->preloadDrivers($userIds);
            $driverIds = array_values($mapDriverIds);

            if (empty($driverIds)) {
                return collect();
            }

            $this->preloadDrivingPeriods($driverIds, $date);
            $this->preloadDriverUtilization($driverIds, $date);
            $this->preloadHistoricDriverUtilization($driverIds, $date);
            $this->preloadHistoricVehicleMileage($date);
        } catch (MotiveClientException $e) {
            Log::error(
                __('messages.service_stats.error_getting_driving_data', ['date' => $this->date->toDateString()]),
                ['exception' => $e->getMessage()]
            );

            return collect();
        }

        $stats = new Collection();

        foreach ($mapDriverIds as $userId => $driverId) {
            $driverStats = $this->getStatsForDriver($driverId, $userId);

            if (is_null($driverStats)) {
                Log::info(__('messages.service_stats.driving_data_not_found', [
                    'date' => $this->date->toDateString(),
                    'workday_id' => $userId,
                ]));

                continue;
            }

            $stats->put($userId, $driverStats);
        }

        return $stats;
    }

    /**
     * @param int $driverId
     * @param string $userId
     *
     * @return RouteDrivingStats|null
     */
    private function getStatsForDriver(int $driverId, string $userId): RouteDrivingStats|null
    {
        $drivingPeriods = $this->preloadedDrivingPeriods->get($driverId) ?? collect();

        if ($drivingPeriods->isEmpty()) {
            return null;
        }

        $driverUtilization = $this->preloadedDriverUtilization->get($driverId) ?? collect();

        $historicDriverUtilization = $this->preloadedHistoricalDriverUtilization->get($driverId) ?? collect();

        // TODO: review the logic of getting fuel consumption
        // Historical fuel consumption should be calculated for used vehicle only
        // Use 'vehicle_utilization' endpoint instead of 'driver_utilization'
        // $historicVehicleUtilization = $this->preloadedHistoricalVehicleUtilization->get($vehicleId) ?? collect();

        $historicVehicleMileage = collect();
        $vehicleIds = $this->driverVehicleMap[$driverId] ?? [];
        foreach ($vehicleIds as $vehicleId) {
            $mileage = $this->preloadedVehicleMileage->get($vehicleId);

            if (!empty($mileage)) {
                $historicVehicleMileage = $historicVehicleMileage->merge($mileage);
            }
        }

        $totalDriveSeconds = $this->getTotalDriveSeconds($drivingPeriods);
        $totalDriveKilometers = $this->getTotalDriveKilometers($drivingPeriods);
        $averageDriveSeconds = $this->getAverageDriveSeconds($drivingPeriods, $totalDriveSeconds);
        $averageDriveKilometers = $this->getAverageDriveKilometers($drivingPeriods, $totalDriveKilometers);
        $totalWorkingTime = $this->getTotalWorkingSeconds($drivingPeriods);
        $totalMileage = $this->getTotalMileage($historicVehicleMileage);

        return new RouteDrivingStats(
            id: $userId,
            totalDriveTime: Duration::fromSeconds($totalDriveSeconds),
            totalDriveDistance: Distance::fromKilometers($totalDriveKilometers),
            averageDriveTimeBetweenServices: Duration::fromSeconds($averageDriveSeconds),
            averageDriveDistanceBetweenServices: Distance::fromKilometers($averageDriveKilometers),
            totalWorkingTime: Duration::fromSeconds($totalWorkingTime),
            fuelConsumption: $this->getFuelConsumption($driverUtilization),
            historicVehicleMileage: Distance::fromMiles($totalMileage),
            historicFuelConsumption: $this->getFuelConsumption($historicDriverUtilization),
        );
    }

    /**
     * Preloads driver periods data for the given IDs.
     *
     * @param int[] $driverIds
     * @param CarbonInterface $date
     *
     * @return void
     * @throws MotiveClientException
     */
    private function preloadDrivingPeriods(array $driverIds, CarbonInterface $date): void
    {
        /** @phpstan-ignore-next-line */
        $this->preloadedDrivingPeriods = $this->client
            ->drivingPeriods()
            ->cached(self::CACHE_DRIVING_PERIODS_TTL, $this->forceUpdateCache)
            ->search(new SearchDrivingPeriodsParams(
                startDate: $date->clone()->startOfDay(),
                endDate: $date->clone()->endOfDay(),
                driverIds: $driverIds,
                type: DrivingPeriodType::DRIVING,
                status: DrivingPeriodStatus::COMPLETE
            ))->groupBy(fn (DrivingPeriod $drivingPeriod) => $drivingPeriod->driverId);

        foreach ($this->preloadedDrivingPeriods as $driverId => $singleDriverPeriods) {
            $this->updateDriverVehicleMap($singleDriverPeriods, $driverId);
        }
    }

    /**
     * Preloads driver utilization data for the given IDs and date.
     *
     * @param int[] $driverIds
     * @param CarbonInterface $date
     *
     * @return void
     * @throws MotiveClientException
     */
    private function preloadDriverUtilization(array $driverIds, CarbonInterface $date): void
    {
        $this->preloadedDriverUtilization = $this->preloadDriverUtilizationForDateRange(
            $driverIds,
            $date->clone()->startOfDay(),
            $date->clone()->endOfDay(),
            $this->forceUpdateCache
        );
    }

    /**
     * Preloads historic driver utilization data for the weeks leading up to the given date.
     *
     * @param int[] $driverIds
     * @param CarbonInterface $date
     *
     * @return void
     * @throws MotiveClientException
     */
    private function preloadHistoricDriverUtilization(array $driverIds, CarbonInterface $date): void
    {
        $this->preloadedHistoricalDriverUtilization = $this->preloadDriverUtilizationForDateRange(
            $driverIds,
            $date->clone()->subWeeks(self::HISTORIC_DATA_DURATION_WEEKS)->startOfDay(),
            $date->clone()->subDay()->endOfDay(),
        );
    }

    /**
     * Preloads driver utilization data for the given IDs and date range.
     *
     * @param int[] $driverIds
     * @param CarbonInterface $startDate
     * @param CarbonInterface $endDate
     * @param bool $forceUpdateCache
     *
     * @return Collection<int, Collection<DriverUtilization>>
     * @throws MotiveClientException
     */
    private function preloadDriverUtilizationForDateRange(
        array $driverIds,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        bool $forceUpdateCache = false
    ): Collection {
        if (empty($driverIds)) {
            return collect();
        }

        /** @phpstan-ignore-next-line */
        return $this->client
            ->driverUtilization()
            ->cached(self::CACHE_DRIVER_UTILIZATION_TTL, $forceUpdateCache)
            ->search(new SearchDriverUtilizationParams(
                startDate: $startDate,
                endDate: $endDate,
                driverIds: $driverIds
            ))->groupBy(fn (DriverUtilization $driverUtilization) => $driverUtilization->driverId);
    }

    /**
     * Preloads historic vehicle mileage data for the weeks leading up to the given date.
     *
     * @param CarbonInterface $date
     *
     * @return void
     * @throws MotiveClientException
     * @throws InvalidArgumentException
     */
    private function preloadHistoricVehicleMileage(CarbonInterface $date): void
    {
        $vehicleIds = array_unique(array_merge(...array_values($this->driverVehicleMap)));

        if (empty($vehicleIds)) {
            $this->preloadedVehicleMileage = collect();

            return;
        }

        /** @phpstan-ignore-next-line */
        $this->preloadedVehicleMileage = $this->client
            ->vehicleMileage()
            ->cached(self::CACHE_VEHICLE_MILEAGE_TTL)
            ->search(new SearchVehicleMileageParams(
                startDate: $date->clone()->subWeeks(self::HISTORIC_DATA_DURATION_WEEKS)->startOfDay(),
                endDate: $date->clone()->subDay()->endOfDay(),
                vehicleIds: $vehicleIds
            ))->groupBy(fn (VehicleMileage $vehicleMileage) => $vehicleMileage->vehicleId);
    }

    private function updateDriverVehicleMap(Collection $drivingPeriods, int $driverId): void
    {
        foreach ($drivingPeriods as $drivingPeriod) {
            if ($drivingPeriod->vehicleId !== null && !in_array($drivingPeriod->vehicleId, $this->driverVehicleMap[$driverId] ?? [])) {
                $this->driverVehicleMap[$driverId][] = $drivingPeriod->vehicleId;
            }
        }
    }

    /**
     * @param Collection<int, DrivingPeriod> $drivingPeriods
     *
     * @return int
     */
    private function getTotalDriveSeconds(Collection $drivingPeriods): int
    {
        return (int) $drivingPeriods->sum(fn (DrivingPeriod $drivingPeriod) => $drivingPeriod->duration);
    }

    /**
     * @param Collection<int, DrivingPeriod> $drivingPeriods
     *
     * @return float
     */
    private function getTotalDriveKilometers(Collection $drivingPeriods): float
    {
        return $drivingPeriods->sum(
            fn (DrivingPeriod $drivingPeriod) => max($drivingPeriod->endKilometers - $drivingPeriod->startKilometers, 0)
        );
    }

    /**
     * @param Collection<int, DrivingPeriod> $drivingPeriods
     * @param int $totalDriveSeconds
     *
     * @return int
     */
    private function getAverageDriveSeconds(Collection $drivingPeriods, int $totalDriveSeconds): int
    {
        return (int) round($totalDriveSeconds / $drivingPeriods->count());
    }

    /**
     * @param Collection<int, DrivingPeriod> $drivingPeriods
     * @param float $totalDriveKilometers
     *
     * @return float
     */
    private function getAverageDriveKilometers(Collection $drivingPeriods, float $totalDriveKilometers): float
    {
        return $totalDriveKilometers / $drivingPeriods->count();
    }

    /**
     * @param Collection<int, DrivingPeriod> $drivingPeriods
     *
     * @return int
     */
    private function getTotalWorkingSeconds(Collection $drivingPeriods): int
    {
        return $drivingPeriods->last()->endTime->diffInSeconds($drivingPeriods->first()->startTime);
    }

    /**
     * @param Collection<int, DriverUtilization> $driverUtilization
     *
     * @return float
     */
    private function getFuelConsumption(Collection $driverUtilization): float
    {
        $totalDrivingFuel = $driverUtilization->sum(fn (DriverUtilization $utilization) => $utilization->drivingFuel);
        $totalIdleFuel = $driverUtilization->sum(fn (DriverUtilization $utilization) => $utilization->idleFuel);

        return (float) ($totalDrivingFuel + $totalIdleFuel);
    }

    /**
     * @param Collection<int, VehicleMileage> $vehicleMileages
     *
     * @return float
     */
    private function getTotalMileage(Collection $vehicleMileages): float
    {
        return (int) $vehicleMileages->sum(fn (VehicleMileage $vehicleMileage) => $vehicleMileage->distance);
    }
}
