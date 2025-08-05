<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\OptimizationStatePersister;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\ValueObjects\RouteGroupType;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\ExtraWork;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Helpers\DateTimeHelper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\EmployeeType;
use Aptive\PestRoutesSDK\Resources\Employees\Params\CreateEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\CreateRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\UpdateRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PestRoutesOptimizationStatePersister implements OptimizationStatePersister
{
    private const PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG = 'isPersistOptimizationDataByDurationEnabled';
    public const RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME = '#Reschedule';
    public const RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME = 'Route#';
    private const DEFAULT_SPOT_DURATION_IN_SECONDS = 1800; //30 minutes
    private const int WORK_EVENT_DURATION_LIMIT_IN_SECONDS_TO_BE_IN_SPOT = 2400; //40 minutes

    /** @var Collection<PestRoutesSpot> */
    private Collection $spots;

    /** @var Collection<PestRoutesAppointment> */
    private Collection $appointments;

    private int $officeId;
    private Office $office;
    private CarbonInterface $date;
    private bool $isPersistOptimizationDataByDurationEnabled = false;

    // TODO: Remove skipBuild feature related code and conditions after PestRoute fix their bug with Appointment::spotId=0
    private bool $isSkipBuildEnabled = true;

    public function __construct(
        private readonly PestRoutesRoutesDataProcessor $routesDataProcessor,
        private readonly PestRoutesEmployeesDataProcessor $employeesDataProcessor,
        private readonly SpotsDataProcessor $spotsDataProcessor,
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly FeatureFlagService $featureFlagService,
    ) {
        $this->spots = new Collection();
        $this->appointments = new Collection();
    }

    /**
     * Persists optimized data to permanent storage
     *
     * @param OptimizationState $optimizationState
     */
    public function persist(OptimizationState $optimizationState): void
    {
        $this->office = $optimizationState->getOffice();
        $this->officeId = $this->office->getId();
        $this->date = $optimizationState->getOptimizationTimeFrame()->getStartAt()->startOfDay();

        $this->resolveOptimizationDataPersistenceAlgorithm();

        $routes = $optimizationState->getRoutes();
        $routeIds = $routes->map(fn (Route $route) => $route->getId())->toArray();

        $this->resolveSpots($routeIds);
        $this->resolveAppointments($routeIds);

        if (!$routes->isEmpty()) {
            $this->saveRoutes($routes);
        }

        $this->storeUnassignedAppointments($optimizationState->getUnassignedAppointments());
    }

    /**
     * @param int[] $routeIds
     *
     * @return void
     */
    private function resolveSpots(array $routeIds): void
    {
        $this->spots = $this->spotsDataProcessor->extract(
            $this->officeId,
            new SearchSpotsParams(
                officeIds: [$this->officeId],
                routeIds: $routeIds,
                skipBuild: $this->isSkipBuildEnabled
            )
        );
    }

    /**
     * @param int[] $routeIds
     *
     * @return void
     */
    private function resolveAppointments(array $routeIds): void
    {
        $this->appointments = $this->appointmentsDataProcessor->extract(
            $this->officeId,
            new SearchAppointmentsParams(
                officeIds: [$this->officeId],
                status: AppointmentStatus::Pending,
                routeIds: $routeIds,
            )
        );
    }

    /**
     * @param int[] $routeIds
     *
     * @return Collection<PestRoutesSpot>
     */
    private function getRouteSpots(array $routeIds): Collection
    {
        return $this->spots->filter(fn (PestRoutesSpot $spot) => in_array($spot->routeId, $routeIds));
    }

    private function getAppointmentById(int $id): PestRoutesAppointment|null
    {
        return $this->appointments->first(fn (PestRoutesAppointment $appointment) => $appointment->id === $id);
    }

    /**
     * Checks if regular route title matches, accounting for pluralization.
     *
     * @param string $title
     *
     * @return bool
     */
    private function isMatchingRegularRouteTitle(string $title): bool
    {
        return RouteGroupType::fromString($title) === RouteGroupType::REGULAR_ROUTE;
    }

    private function resolveOptimizationDataPersistenceAlgorithm(): void
    {
        $this->isPersistOptimizationDataByDurationEnabled = $this->featureFlagService->isFeatureEnabledForOffice(
            $this->officeId,
            self::PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG,
        );
    }

    /**
     * @param Collection<Route> $routes
     *
     * @return void
     */
    private function saveRoutes(Collection $routes): void
    {
        $spots = $this->getRouteSpots(
            routeIds: $routes->map(fn (Route $route) => $route->getId())->toArray(),
        );
        $blockedSpots = $spots->filter(fn (PestRoutesSpot $spot) => $spot->capacity === 0);
        $this->spotsDataProcessor->unblockMultiple($this->officeId, $blockedSpots);

        /** @var Collection<int, Collection<PestRoutesSpot>> $groupedSpots */
        $groupedSpots = $spots->groupBy('routeId');

        foreach ($routes as $route) {
            $this->saveRoute($route, $groupedSpots->get($route->getId()));
        }
    }

    private function storeUnassignedAppointments(Collection $unassignedAppointments): void
    {
        if ($unassignedAppointments->isEmpty()) {
            return;
        }

        $rescheduleRouteId = $this->getRescheduleRouteId();

        /** @var Appointment $appointment */
        foreach ($unassignedAppointments as $appointment) {
            $this->appointmentsDataProcessor->assignAppointment(
                $this->officeId,
                $rescheduleRouteId,
                $appointment->getId()
            );
        }
    }

    private function saveRoute(Route $route, Collection $routeSpots): void
    {
        // First we have to block ReservedTime spots and meetings
        $workEvents = $route->getReservedTimes()->merge($route->getMeetings());

        foreach ($workEvents as $workEvent) {
            $this->assignReservedTimeToRouteAndRemoveSpotFromCollection($workEvent, $routeSpots);
        }

        $workEvents = $route->getWorkEvents()->filter(function (WorkEvent $workEvent) {
            return $workEvent instanceof WorkBreak
                || $workEvent instanceof Appointment
                || $workEvent instanceof ExtraWork;
        })->sortBy(fn (WorkEvent $workEvent) => $workEvent->getTimeWindow()?->getStartAt())->values();

        if ($this->isPersistOptimizationDataByDurationEnabled) {
            $this->assignWorkEventsOrderByDuration($workEvents, $routeSpots);
        } else {
            $this->assignWorkEvents($workEvents, $routeSpots);
        }
    }

    /**
     * @param Collection<WorkEvent> $workEvents
     * @param Collection<PestRoutesSpot> $spots
     *
     * @return void
     */
    private function assignWorkEvents(Collection $workEvents, Collection $spots): void
    {
        $spots = $spots->sortBy('start')->values();

        $assignedIds = [];
        $blockedIds = [];
        foreach ($workEvents as $workEvent) {
            if ($workEvent instanceof Appointment && $workEvent->getTimeWindow() === null) {
                $this->assignAppointmentToRoute($workEvent);

                continue;
            }

            $spot = $this->findSpot($workEvent, $spots, $assignedIds, $blockedIds);

            if ($spot === null) {
                break;
            }

            $this->assignWorkEventToSpot($spot, $workEvent);
            if ($workEvent instanceof Appointment) {
                $assignedIds[] = $spot->id;
            }

            if ($workEvent instanceof WorkBreak || $workEvent instanceof ExtraWork) {
                $blockedIds[] = $spot->id;
            }
        }
    }

    /**
     * @param ReservedTime|Meeting $workEvent
     * @param Collection<PestRoutesSpot> $spots
     *
     * @return void
     */
    private function assignReservedTimeToRouteAndRemoveSpotFromCollection(
        ReservedTime|Meeting $workEvent,
        Collection $spots
    ): void {
        /** @var Collection<PestRoutesSpot> $reservedSpots */
        $reservedSpots = new Collection();

        foreach ($spots as $key => $spot) {
            $spotStart = Carbon::instance($spot->start);
            $spotEnd = Carbon::instance($spot->end);

            $isReservedTimeStartInSpot = $workEvent->getTimeWindow()->getStartAt()->between(
                $spotStart,
                $spotEnd,
            );
            $isSpotInsideReservedTime = $workEvent->getTimeWindow()->isDateInTimeWindow($spotStart)
                && $workEvent->getTimeWindow()->isDateInTimeWindow($spotEnd);

            if ($isReservedTimeStartInSpot || $isSpotInsideReservedTime) {
                $reservedSpots->add($spot);
                $spots->forget([$key]);
            }
        }

        if ($reservedSpots->isNotEmpty()) {
            $this->spotsDataProcessor->blockMultiple(
                $reservedSpots->first()->officeId,
                $reservedSpots,
                $workEvent->getFormattedDescription()
            );
        }
    }

    /**
     * @param WorkEvent $workEvent
     * @param Collection<PestRoutesSpot> $allSpots
     * @param int[] $assignedIds
     * @param int[] $blockedIds
     *
     * @return PestRoutesSpot|null
     */
    private function findSpot(WorkEvent $workEvent, Collection $allSpots, array $assignedIds, array $blockedIds): PestRoutesSpot|null
    {
        if ($workEvent instanceof Appointment) {
            $spots = $allSpots->filter(
                fn (PestRoutesSpot $spot) => !in_array($spot->id, $blockedIds)
            )->values();
        } else {
            $spots = $allSpots->filter(
                fn (PestRoutesSpot $spot) => !in_array($spot->id, $assignedIds) && !in_array($spot->id, $blockedIds)
            )->values();
        }

        if ($spots->isEmpty()) {
            return null;
        }

        if ($workEvent->getTimeWindow() === null) {
            return null;
        }

        $i = 0;
        $currentSpot = $spots->get($i);
        do {
            /** @var PestRoutesSpot $currentSpot */
            $currentSpot = $spots->get($i, $currentSpot);
            /** @var PestRoutesSpot $nextSpot */
            $nextSpot = $spots->get(++$i);
            if ($nextSpot === null) {
                break;
            }

            $rightBorderTime = Carbon::instance($currentSpot->end)->addMinute();
        } while (
            $currentSpot->start < $workEvent->getTimeWindow()->getStartAt()
            && $rightBorderTime < $workEvent->getTimeWindow()->getStartAt()
        );

        return $currentSpot;
    }

    /**
     * @param WorkEvent $workEvent
     * @param Collection<PestRoutesSpot> $allSpots
     * @param int[] $assignedIds
     *
     * @return Collection<PestRoutesSpot>
     */
    private function findAssignedSpots(WorkEvent $workEvent, Collection $allSpots, array $assignedIds): Collection
    {
        $spots = $allSpots->filter(
            fn (PestRoutesSpot $spot) => !in_array($spot->id, $assignedIds),
        )->sortBy('start')->values();

        $assignedSpots = new Collection();

        if ($spots->isEmpty()) {
            return $assignedSpots;
        }

        $combinedDurationInSeconds = 0;
        $workEventDurationInSeconds = $workEvent->getDuration()->getTotalSeconds();

        if ($workEventDurationInSeconds < self::WORK_EVENT_DURATION_LIMIT_IN_SECONDS_TO_BE_IN_SPOT) {
            $workEventDurationInSeconds = self::DEFAULT_SPOT_DURATION_IN_SECONDS;
        }

        /** @var Collection<PestRoutesSpot> $spots */
        foreach ($spots as $spot) {
            $spotDurationInSeconds = max(
                $this->getDurationInSeconds($spot->end->diff($spot->start)),
                self::DEFAULT_SPOT_DURATION_IN_SECONDS,
            );
            $combinedDurationInSeconds += $spotDurationInSeconds;

            $assignedSpots->add($spot);

            if ($combinedDurationInSeconds >= $workEventDurationInSeconds) {
                break;
            }
        }

        return $assignedSpots;
    }

    private function getDurationInSeconds(DateInterval $interval): int
    {
        return $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    /**
     * Assigns work events to spots. If there are less spots than work events, it will remove extra work events
     * and then also assign remaining work events to the last spot.
     *
     * @param Collection<WorkEvent>      $workEvents
     * @param Collection<PestRoutesSpot> $spots
     *
     * @return void
     */
    private function assignWorkEventsOrderByDuration(Collection $workEvents, Collection $spots): void
    {
        $spots = $spots
            ->sortBy('start')
            ->values();

        $this->adjustWorkEventsForAvailableSpots($workEvents, $spots);
        $this->assignEventsToSpots($workEvents, $spots);
    }

    private function assignEventsToSpots(Collection $workEvents, Collection $spots): void
    {
        $assignedIds = [];
        $assignedIndexes = [];

        foreach ($workEvents as $index => $workEvent) {
            if ($workEvent instanceof Appointment && $workEvent->getTimeWindow() === null) {
                $this->assignAppointmentToRoute($workEvent);
                $assignedIndexes[] = $index;

                continue;
            }

            $assignedSpots = $this->findAssignedSpots($workEvent, $spots, $assignedIds);

            if ($assignedSpots->isEmpty()) {
                break;
            }

            $assignedIndexes[] = $index;

            if ($workEvent instanceof ReservedTime) {
                foreach ($assignedSpots as $assignedSpot) {
                    $this->assignReservedTimeToSpot($assignedSpot, $workEvent);
                }
            } else {
                $this->assignWorkEventToSpot($assignedSpots->first(), $workEvent);
            }

            $assignedIds = array_merge($assignedIds, $assignedSpots->pluck('id')->toArray());
        }

        $remainingWorkEvents = $workEvents->reject(function (WorkEvent $workEvent, $index) use ($assignedIndexes) {
            return in_array($index, $assignedIndexes);
        });

        if (!$remainingWorkEvents->isEmpty()) {
            $this->assignRemainingWorkEventsToLastSpot($remainingWorkEvents, $spots);
        }
    }

    /**
     * Removes ExtraWork work events one by one in reversed order until we have enough spots
     *
     * @param Collection $workEvents
     * @param Collection $spots
     *
     * @return void
     */
    private function adjustWorkEventsForAvailableSpots(Collection $workEvents, Collection $spots): void
    {
        while ($workEvents->count() > $spots->count()) {
            $removedExtraWork = false;

            foreach ($workEvents->reverse() as $index => $workEvent) {
                if ($workEvent instanceof ExtraWork) {
                    $workEvents->offsetUnset($index);
                    $removedExtraWork = true;

                    break;
                }
            }

            if (!$removedExtraWork) {
                break;
            }
        }
    }

    private function assignRemainingWorkEventsToLastSpot(Collection $workEvents, Collection $spots): void
    {
        $lastSpot = $spots->last();

        foreach ($workEvents as $workEvent) {
            $this->assignWorkEventToSpot($lastSpot, $workEvent);
        }
    }

    private function assignWorkEventToSpot(PestRoutesSpot $spot, WorkEvent $workEvent): void
    {
        match (get_class($workEvent)) {
            Appointment::class => $this->assignAppointmentToSpot($spot, $workEvent),
            Lunch::class, WorkBreak::class => $this->assignBreakToSpot($spot, $workEvent),
            ExtraWork::class => $this->assignExtraWorkToSpot($spot, $workEvent),
            default => null,
        };
    }

    private function assignAppointmentToRoute(Appointment $appointment): void
    {
        $this->appointmentsDataProcessor->assignAppointment(
            $appointment->getOfficeId(),
            $appointment->getRouteId(),
            $appointment->getId()
        );
    }

    private function assignAppointmentToSpot(PestRoutesSpot $spot, Appointment $appointment): bool
    {
        if (is_array($spot->appointmentIds) && in_array($appointment->getId(), $spot->appointmentIds, true)) {
            return true;
        }

        $pestRoutesAppointment = $this->getAppointmentById($appointment->getId());

        if ($pestRoutesAppointment !== null && $pestRoutesAppointment->spotId === $spot->id) {
            return true;
        }

        return $this->appointmentsDataProcessor->assignAppointment(
            $spot->officeId,
            $appointment->getRouteId(),
            $appointment->getId(),
            $spot->id
        );
    }

    private function assignReservedTimeToSpot(PestRoutesSpot $spot, ReservedTime $reservedTime): void
    {
        $this->spotsDataProcessor->block($spot->officeId, $spot->id, $reservedTime->getDescription());
    }

    private function assignBreakToSpot(PestRoutesSpot $spot, WorkBreak $workBreak): void
    {
        $this->spotsDataProcessor->block($spot->officeId, $spot->id, $workBreak->getFormattedFullDescription());
    }

    private function assignExtraWorkToSpot(PestRoutesSpot $spot, ExtraWork $workEvent): void
    {
        $this->spotsDataProcessor->block($spot->officeId, $spot->id, $workEvent->getFormattedDescription());
    }

    private function getRescheduleRouteEmployee(): PestRoutesEmployee
    {
        $searchParams = new SearchEmployeesParams(
            officeIds: [$this->officeId],
            isActive: true,
            lastName: self::RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME,
            firstName: self::RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME
        );

        $employee = $this->employeesDataProcessor->extract($this->officeId, $searchParams)->first();

        if ($employee !== null) {
            return $employee;
        }

        $this->employeesDataProcessor->create($this->officeId, new CreateEmployeesParams(
            firstName: self::RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME,
            lastName: self::RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME,
            isActive: true,
            type: EmployeeType::Technician,
            officeId: $this->officeId
        ));

        return $this->employeesDataProcessor->extract($this->officeId, $searchParams)->first();
    }

    private function getRescheduleRouteId(): int
    {
        $employee = $this->getRescheduleRouteEmployee();

        $searchRoutesParams = new SearchRoutesParams(
            officeIds: [$this->officeId],
            dateStart: Carbon::instance($this->date)->startOfDay(),
            dateEnd: Carbon::instance($this->date)->endOfDay(),
        );

        $allRoutes = $this->routesDataProcessor->extract($this->officeId, $searchRoutesParams)
            ->filter(
                fn (PestRoutesRoute $route) => !$this->deleteAssignedRouteFromNonRegularGroups($employee, $route)
            )
            ->filter(
                fn (PestRoutesRoute $route) => $this->isMatchingRegularRouteTitle($route->groupTitle)
            );

        /** @var PestRoutesRoute $rescheduleRoute */
        $rescheduleRoute = $allRoutes->filter(
            fn (PestRoutesRoute $route) => $route->assignedTech === $employee->id
        )->first();

        if ($rescheduleRoute) {
            $rescheduleRouteSpots = $this->getRouteSpots([$rescheduleRoute->id]);

            if (!$rescheduleRouteSpots->isEmpty()) {
                return $rescheduleRoute->id;
            }

            $this->routesDataProcessor->delete($this->officeId, $rescheduleRoute->id);
        }

        /** @var PestRoutesRoute $route */
        $route = $allRoutes->first();

        //TODO: https://aptive.atlassian.net/browse/AARO-430
        //We need to set autoCreateGroup to true to get over the bug in PestRoutes API
        //But we need to remove that parameter as soon as the bug is fixed
        $this->routesDataProcessor->create($this->officeId, new CreateRoutesParams(
            date: $this->date->format(DateTimeHelper::DATE_FORMAT),
            assignedTech: $employee->id,
            autoCreateGroup: true,
            officeId: $this->officeId,
            groupId: $route->groupId,
        ));

        $allRoutes = $this->routesDataProcessor->extract($this->officeId, $searchRoutesParams);

        /** @var PestRoutesRoute $newRescheduleRoute */
        $newRescheduleRoute = $allRoutes->sortBy(fn (PestRoutesRoute $route) => $route->dateAdded)->last();
        $this->routesDataProcessor->update($this->officeId, new UpdateRoutesParams(
            routeId: $newRescheduleRoute->id,
            assignedTechId: $employee->id,
            officeId: $this->officeId
        ));

        $rescheduleRouteSpots = $this->getRouteSpots([$newRescheduleRoute->id]);
        $this->spotsDataProcessor->unblockMultiple($this->officeId, $rescheduleRouteSpots);

        return $newRescheduleRoute->id;
    }

    /**
     * Deletes reschedule route if it is not in regular route group
     *
     * @param PestRoutesEmployee $employee
     * @param PestRoutesRoute $route
     *
     * @return bool
     */
    private function deleteAssignedRouteFromNonRegularGroups(PestRoutesEmployee $employee, PestRoutesRoute $route): bool
    {
        if ($route->assignedTech === $employee->id && !$this->isMatchingRegularRouteTitle($route->groupTitle)) {
            $this->routesDataProcessor->delete($this->officeId, $route->id);
            Log::notice(__('messages.routes_optimization.route_deleted_from_non_regular_group', [
                'office_id' => $this->officeId,
                'route_id' => $route->id,
            ]));

            return true;
        }

        return false;
    }
}
