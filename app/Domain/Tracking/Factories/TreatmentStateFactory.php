<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Factories;

use App\Domain\Contracts\Repositories\ServicedRoutesRepository;
use App\Domain\Contracts\Services\RouteDrivingStatsService;
use App\Domain\Contracts\Services\VehicleTrackingDataService;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\Entities\ServicedRoute;
use App\Domain\Tracking\ValueObjects\TreatmentStateIdentity;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

// TODO: Add tests for this class
class TreatmentStateFactory
{
    private Office $office;
    private CarbonInterface $date;

    /** @var Collection<ServicedRoute> */
    private Collection $servicedRoutes;

    /** @var Collection<RouteTrackingData> */
    private Collection $trackingData;

    /** @var Collection<RouteDrivingStats> */
    private Collection $drivingStats;

    public function __construct(
        private readonly ServicedRoutesRepository $servicedRoutesRepository,
        private VehicleTrackingDataService $trackingDataService,
        private RouteDrivingStatsService $drivingStatsService,
    ) {
    }

    /**
     * It creates a new TreatmentState instance
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return TreatmentState
     */
    public function create(Office $office, CarbonInterface $date): TreatmentState
    {
        $this->office = $office;
        $this->date = $date;

        $this->servicedRoutes = collect();
        $this->trackingData = collect();
        $this->drivingStats = collect();

        try {
            $this->resolveServicedRoutes();
            $this->resolveTrackingData();
            $this->resolveDrivingStats();
        } catch (NoRegularRoutesFoundException|NoServiceProFoundException $e) {
        }

        return new TreatmentState(
            id: new TreatmentStateIdentity(officeId: $office->getId(), date: $date->clone()),
            servicedRoutes: $this->servicedRoutes,
            trackingData: $this->trackingData,
            drivingStats: $this->drivingStats
        );
    }

    /**
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     */
    private function resolveServicedRoutes(): void
    {
        $this->servicedRoutes = $this->servicedRoutesRepository->findByOfficeAndDate(
            $this->office,
            $this->date
        );
    }

    private function resolveTrackingData(): void
    {
        if (!$this->isToday()) {
            return;
        }

        $this->trackingData = $this->trackingDataService->get($this->getUserIds(), $this->date);
    }

    private function resolveDrivingStats(): void
    {
        if (!$this->canUpdateStats()) {
            return;
        }

        $this->drivingStats = $this->drivingStatsService->get($this->getUserIds(), $this->date);
    }

    /**
     * @return string[]
     */
    private function getUserIds(): array
    {
        return $this->servicedRoutes->map(
            fn (ServicedRoute $route) => $route->getServicePro()->getWorkdayId()
        )->toArray();
    }

    private function isToday(): bool
    {
        $nowInOffice = Carbon::now($this->date->getTimezone());

        return $this->date->isSameDay($nowInOffice);
    }

    private function canUpdateStats(): bool
    {
        $todayInOffice = Carbon::today($this->date->getTimezone());

        return $this->date->lessThan($todayInOffice->endOfDay());
    }
}
