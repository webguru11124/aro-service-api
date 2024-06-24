<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes;

use App\Application\Events\RouteExcluded;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\Contracts\OptimizationStateResolver;
use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\RouteOptimization\ValueObjects\RouteGroupType;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Exceptions\NoAppointmentsFoundException;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Exceptions\RoutesHaveNoCapacityException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentRemindersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesAppointmentTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesRouteTranslator;
use App\Infrastructure\Services\PestRoutes\Scopes\PestRoutesBlockedSpotReasons;
use Aptive\PestRoutesSDK\Filters\NumberFilter;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminder as PestRoutesAppointmentReminders;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminderStatus;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\SearchAppointmentRemindersParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PestRoutesOptimizationStateResolver implements OptimizationStateResolver
{
    private const string ROUTE_OPTIMIZATION_ENGINE_FEATURE_FLAG = 'whichRouteOptimizationEngineForOfficeIsSelected';
    private const string PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG = 'isPestroutesSkipBuildEnabled';
    public const string RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME = '#Reschedule';
    public const string RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME = 'Route#';

    /** @var Collection<PestRoutesRoute> */
    private Collection $routes;

    /** @var Collection<PestRoutesSpot> */
    private Collection $spots;

    /** @var Collection<PestRoutesEmployee> */
    private Collection $employees;

    /** @var Collection<PestRoutesAppointment> */
    private Collection $appointments;

    /** @var Collection<PestRoutesCustomer> */
    private Collection $customers;

    /** @var Collection<PestRoutesServiceType> */
    private Collection $serviceTypes;

    /** @var Collection<int, Collection<PestRoutesAppointmentReminders>> */
    private Collection $appointmentReminders;

    /** @var Collection<Appointment> */
    private Collection $overbookedAppointments;

    private int $officeId;
    private Office $office;
    private CarbonInterface $date;

    // TODO: Remove skipBuild feature related code and conditions after PestRoute fix their bug with Appointment::spotId=0
    private bool $isSkipBuildEnabled = true;

    public function __construct(
        private readonly OptimizationStateRepository $stateRepository,
        private readonly PestRoutesRouteTranslator $routeTranslator,
        private readonly PestRoutesAppointmentTranslator $appointmentTranslator,
        private readonly PestRoutesRoutesDataProcessor $routesDataProcessor,
        private readonly PestRoutesEmployeesDataProcessor $employeesDataProcessor,
        private readonly SpotsDataProcessor $spotsDataProcessor,
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly PestRoutesCustomersDataProcessor $customersDataProcessor,
        private readonly PestRoutesAppointmentRemindersDataProcessor $appointmentRemindersDataProcessor,
        private readonly ServiceTypesDataProcessor $serviceTypeDataProcessor,
        private readonly FeatureFlagService $featureFlagService,
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
     * @throws InvalidTimeWindowException
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     * @throws NoAppointmentsFoundException
     * @throws RoutesHaveNoCapacityException
     */
    public function resolve(
        CarbonInterface $date,
        Office $office,
        OptimizationParams $optimizationParams,
    ): OptimizationState {
        $this->date = $date;
        $this->office = $office;
        $this->officeId = $office->getId();

        $optimizationState = new OptimizationState(
            $this->stateRepository->getNextId(),
            $this->selectOptimizationEngine(),
            OptimizationStatus::PRE,
            Carbon::now(),
            $office,
            new TimeWindow(
                Carbon::instance($date)->startOfDay(),
                Carbon::instance($date)->endOfDay()
            ),
            $optimizationParams,
        );

        $this->resolveSkipBuildFeature();
        $this->resolveRegularRoutes();
        $this->resolveSpots();
        $this->filterAvailableRoutes();
        $this->resolveEmployees();
        $this->resolveAppointments();
        $this->excludeRoutesWithLockedAppointments();
        $this->resolveCustomers();
        $this->resolveServiceTypes();
        $this->resolveAppointmentReminders();
        $this->excludeRoutesWithMissingAppointmentServiceType();

        $this->addRoutesToOptimizationState($optimizationState);
        $this->validateRoutesCapacity($optimizationState);

        return $optimizationState;
    }

    private function resolveRegularRoutes(): void
    {
        $allRoutes = $this->routesDataProcessor->extract(
            $this->officeId,
            new SearchRoutesParams(
                officeIds: [$this->officeId],
                dateStart: $this->date->clone()->startOfDay(),
                dateEnd: $this->date->clone()->endOfDay(),
            )
        );

        $this->routes = $allRoutes->filter(
            fn (PestRoutesRoute $route) => $this->isMatchingRegularRouteTitle($route->groupTitle)
        );

        if ($this->routes->isEmpty()) {
            throw NoRegularRoutesFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }
    }

    private function resolveSpots(): void
    {
        $routeIds = $this->routes->pluck('id')->toArray();

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
     * @param int $routeId
     *
     * @return Collection<PestRoutesSpot>
     */
    private function getRouteSpots(int $routeId): Collection
    {
        return $this->spots->filter(fn (PestRoutesSpot $spot) => $spot->routeId === $routeId);
    }

    private function getSpotById(int $id): PestRoutesSpot|null
    {
        return $this->spots->first(fn (PestRoutesSpot $spot) => $spot->id === $id);
    }

    /**
     * @throws NoRegularRoutesFoundException
     */
    private function filterAvailableRoutes(): void
    {
        $this->excludeRoutesWithReservedSpots();
        $this->excludeRoutesUnavailableForApi();
        $this->excludeRoutesWithoutSpots();
        $this->excludeLockedRoutes();

        if ($this->routes->isEmpty()) {
            throw NoRegularRoutesFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }
    }

    /**
     * @throws RoutesHaveNoCapacityException
     */
    private function validateRoutesCapacity(OptimizationState $optimizationState): void
    {
        $totalCapacity = $optimizationState->getRoutes()->sum(
            fn (Route $route) => $route->getMaxCapacity()
        );

        if ($optimizationState->getRoutes()->count() > 0 && $totalCapacity === 0) {
            throw RoutesHaveNoCapacityException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }
    }

    private function excludeRoutesWithReservedSpots(): void
    {
        $filteredSpots = $this->spots->filter(fn (PestRoutesSpot $spot) => $spot->isReserved);

        if (!$filteredSpots->isEmpty()) {
            $routeIds = $filteredSpots->pluck('routeId')->unique()->filter()->toArray();
            $this->routes = $this->routes->filter(
                fn (PestRoutesRoute $route) => !in_array($route->id, $routeIds)
            );
            Log::notice(__('messages.routes_optimization.route_has_reserved_spots_not_optimized', [
                'office' => $this->office->getName(),
                'office_id' => $this->office->getId(),
                'date' => $this->date->toDateString(),
            ]), [
                'routeIds' => $routeIds,
            ]);
        }
    }

    private function excludeRoutesUnavailableForApi(): void
    {
        $filteredRoutes = $this->routes->filter(fn (PestRoutesRoute $route) => !$route->apiCanSchedule);

        if (!$filteredRoutes->isEmpty()) {
            $this->routes = $this->routes->filter(fn (PestRoutesRoute $route) => $route->apiCanSchedule);
            Log::notice(__('messages.routes_optimization.route_without_api_in_who_can_schedule', [
                'office' => $this->office->getName(),
                'office_id' => $this->office->getId(),
                'date' => $this->date->toDateString(),
            ]), [
                'routeIds' => $filteredRoutes->pluck('routeId')->toArray(),
            ]);
        }
    }

    private function excludeRoutesWithoutSpots(): void
    {
        $groupedSpots = $this->spots->groupBy('routeId');
        $this->routes = $this->routes->filter(
            fn (PestRoutesRoute $route) => !is_null($groupedSpots->get($route->id))
        );
    }

    private function excludeRoutesWithMissingAppointmentServiceType(): void
    {
        $appointmentsWithMissingServiceType = $this->appointments->filter(
            fn (PestRoutesAppointment $appointment) => empty($this->serviceTypes[$appointment->serviceTypeId])
        );

        if (!$appointmentsWithMissingServiceType->isEmpty()) {
            $routeIds = $appointmentsWithMissingServiceType->pluck('routeId')->unique()->filter()->values()->toArray();
            $appointmentIds = $appointmentsWithMissingServiceType->pluck('id')->toArray();
            $missingServiceTypeIds = $appointmentsWithMissingServiceType->pluck('serviceTypeId')->unique()->values()->toArray();

            $this->routes = $this->routes->filter(
                fn (PestRoutesRoute $route) => !in_array($route->id, $routeIds)
            );

            Log::warning(__('messages.routes_optimization.appointment_service_type', [
                'office' => $this->office->getName(),
                'office_id' => $this->office->getId(),
                'date' => $this->date->toDateString(),
                'service_type_ids' => implode(', ', $missingServiceTypeIds),
            ]), [
                'routeIds' => $routeIds,
                'appointment_ids' => $appointmentIds,
            ]);
        }
    }

    /**
     * Excludes routes with lockedRoute flag set to true
     */
    private function excludeLockedRoutes(): void
    {
        $routes = new Collection();

        /** @var PestRoutesRoute $route*/
        foreach ($this->routes as $route) {
            if ($route->lockedRoute) {
                Log::notice(__('messages.routes_optimization.route_locked', [
                    'office' => $this->office->getName(),
                    'office_id' => $this->office->getId(),
                    'date' => $this->date->toDateString(),
                    'route_id' => $route->id,
                ]));

                continue;
            }
            $routes->add($route);
        }

        $this->routes = $routes;
    }

    private function excludeRoutesWithLockedAppointments(): void
    {
        $lockedAppointments = $this->appointments->filter(
            fn (PestRoutesAppointment $appointment) => !empty($appointment->lockedBy)
        );

        if ($lockedAppointments->isNotEmpty()) {
            $routeIds = $lockedAppointments->pluck('routeId')->unique()->toArray();

            $this->routes = $this->routes->reject(
                fn (PestRoutesRoute $route) => in_array($route->id, $routeIds)
            );

            $this->appointments = $this->appointments->diffUsing($lockedAppointments, function ($a, $b) {
                return $a->id - $b->id;
            });

            Log::notice(__('messages.routes_optimization.routes_with_locked_appointments', [
                'office' => $this->office->getName(),
                'office_id' => $this->office->getId(),
                'date' => $this->date->toDateString(),
            ]), [
                'routeIds' => $routeIds,
            ]);
            RouteExcluded::dispatch(
                $routeIds,
                $this->office,
                $this->date,
                __('messages.notifications.route_excluded.reason.locked_appointments'),
            );
        }
    }

    /**
     * @throws NoServiceProFoundException
     */
    private function resolveEmployees(): void
    {
        $employeeIds = $this->routes
            ->map(fn (PestRoutesRoute $route) => $route->assignedTech)
            ->filter(fn ($assignedTech) => $assignedTech !== null)
            ->unique()
            ->toArray();

        if (empty($employeeIds)) {
            throw NoServiceProFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }

        $this->employees = $this->employeesDataProcessor->extract(
            $this->officeId,
            new SearchEmployeesParams(
                officeIds: [$this->officeId],
                employeeIds: $employeeIds
            )
        )->keyBy('id');

        if ($this->employees->isEmpty()) {
            throw NoServiceProFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }
    }

    /**
     * @return void
     * @throws NoAppointmentsFoundException
     */
    private function resolveAppointments(): void
    {
        $routeIds = $this->routes->pluck('id')->toArray();

        $this->appointments = $this->appointmentsDataProcessor->extract(
            $this->officeId,
            new SearchAppointmentsParams(
                officeIds: [$this->officeId],
                status: AppointmentStatus::Pending,
                routeIds: $routeIds,
            )
        );

        if ($this->appointments->isEmpty()) {
            throw NoAppointmentsFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }
    }

    private function resolveCustomers(): void
    {
        $customerIds = $this->appointments->pluck('customerId')->toArray();

        $this->customers = $this->customersDataProcessor->extract(
            $this->officeId,
            new SearchCustomersParams(
                ids: $customerIds,
                officeIds: [$this->officeId],
                includeCancellationReason: false,
                includeSubscriptions: false,
                includeCustomerFlag: false,
                includeAdditionalContacts: false,
                includePortalLogin: false,
            )
        )->keyBy('id');
    }

    private function resolveServiceTypes(): void
    {
        $serviceTypeIds = $this->appointments->pluck('serviceTypeId')->unique()->filter()->toArray();

        $this->serviceTypes = $this->serviceTypeDataProcessor->extract(
            $this->officeId,
            new SearchServiceTypesParams(
                ids: $serviceTypeIds,
                officeIds: [$this->officeId]
            )
        )->keyBy('id');
    }

    private function resolveAppointmentReminders(): void
    {
        $appointmentIds = $this->appointments->pluck('id')->toArray();

        /** @var Collection<int, Collection<PestRoutesAppointmentReminders>> $appointmentReminders */
        $appointmentReminders = $this->appointmentRemindersDataProcessor->extract(
            $this->officeId,
            new SearchAppointmentRemindersParams(
                appointmentId: NumberFilter::in($appointmentIds),
                status: NumberFilter::in([
                    AppointmentReminderStatus::CONFIRMED_BY_OFFICE->value,
                    AppointmentReminderStatus::CONFIRMED_VIA_SMS->value,
                ])
            )
        )->groupBy('appointmentId');

        $this->appointmentReminders = $appointmentReminders;
    }

    private function getReservedTimesForRoute(Route $route): Collection
    {
        $reservedTimes = new Collection();
        $spots = $this->filterReservedTimeSpotsByRoute($route);

        /** @var PestRoutesSpot|null $previousSpot */
        $previousSpot = $startAt = $endAt = null;

        /** @var PestRoutesSpot $spot */
        foreach ($spots as $spot) {
            if ($previousSpot === null) {
                $startAt = Carbon::instance($spot->start);
                $endAt = Carbon::instance($spot->end);
                $previousSpot = $spot;

                continue;
            }

            $currentStart = Carbon::instance($spot->start);
            $currentEnd = Carbon::instance($spot->end);

            if (!$this->areConsecutiveSpotsWithSameReason($previousSpot, $spot)) {
                $this->addReservedTime($reservedTimes, $previousSpot, new TimeWindow($startAt, $endAt));

                $startAt = $currentStart->clone();
            }

            $endAt = $currentEnd->clone();
            $previousSpot = $spot;
        }

        if ($previousSpot !== null) {
            $this->addReservedTime($reservedTimes, $previousSpot, new TimeWindow($startAt, $endAt));
        }

        return $reservedTimes;
    }

    private function addReservedTime(Collection $reservedTimes, PestRoutesSpot $spot, TimeWindow $timeWindow): void
    {
        $reservedTime = (new ReservedTime(
            id: $spot->id,
            description: $spot->blockReason,
        ))
            ->setExpectedArrival($timeWindow)
            ->setTimeWindow($timeWindow)
            ->setDuration($timeWindow->getDuration())
            ->setRouteId($spot->routeId);

        $reservedTimes->add($reservedTime);
    }

    private function areConsecutiveSpotsWithSameReason(PestRoutesSpot $previousSpot, PestRoutesSpot $currentSpot): bool
    {
        $previousEnd = Carbon::instance($previousSpot->end);
        $currentStart = Carbon::instance($currentSpot->start);

        return $previousEnd->addMinute()->equalTo($currentStart)
            && strtolower(trim($previousSpot->blockReason)) === strtolower(trim($currentSpot->blockReason));
    }

    /**
     * @param Route $route
     *
     * @return Collection<PestRoutesSpot>
     */
    private function filterReservedTimeSpotsByRoute(Route $route): Collection
    {
        return $this->spots->filter(function (PestRoutesSpot $spot) use ($route) {
            if ($spot->capacity > 0 || is_null($spot->blockReason)) {
                return false;
            }

            $blockReasonContainsMarker = array_reduce(
                PestRoutesBlockedSpotReasons::PROCESSABLE_ON_OPTIMIZATION,
                function ($carry, $marker) use ($spot) {
                    return $carry || stripos($spot->blockReason, $marker) !== false;
                },
                false
            );

            return !$blockReasonContainsMarker && $spot->routeId === $route->getId();
        })->sortBy('start');
    }

    private function addRoutesToOptimizationState(OptimizationState $optimizationState): void
    {
        $this->overbookedAppointments = new Collection();

        /** @var PestRoutesRoute $route */
        foreach ($this->routes as $route) {
            $employee = $this->employees->get($route->assignedTech);

            /** @var Collection<Appointment> $appointments */
            $appointments = $this->getRouteAppointments($route->id);

            if (!$employee) {
                $optimizationState->addUnassignedAppointment(...$appointments->toArray());

                continue;
            }

            $routeSpots = $this->getRouteSpots($route->id);
            $domainRoute = $this->routeTranslator->toDomain($route, $employee, $routeSpots);

            $extraBreaks = $this->getReservedTimesForRoute($domainRoute);
            $breaks = $this->getRouteBreaks($route->id);
            $workEvents = $appointments->merge($breaks)->merge($extraBreaks);
            $domainRoute->addWorkEvents($workEvents->values());
            $optimizationState->addRoute($domainRoute);
        }

        if ($this->overbookedAppointments->isNotEmpty()) {
            $optimizationState->addUnassignedAppointment(...$this->overbookedAppointments);
        }
    }

    private function getRouteAppointments(int $routeId): Collection
    {
        $appointmentsOnRoute = $this->appointments->filter(
            fn (PestRoutesAppointment $appointment) => $appointment->routeId === $routeId
        );

        $appointments = new Collection();

        /** @var PestRoutesAppointment $pestRoutesAppointment */
        foreach ($appointmentsOnRoute as $pestRoutesAppointment) {
            $spot = $this->getSpotAndUpdateAppointmentSpotId($pestRoutesAppointment);
            $appointment = $this->appointmentTranslator->toDomain(
                $pestRoutesAppointment,
                $this->customers->get($pestRoutesAppointment->customerId),
                $this->serviceTypes->get($pestRoutesAppointment->serviceTypeId),
                $spot,
                $this->appointmentReminders->get($pestRoutesAppointment->id)
            );

            if (is_null($spot)) {
                $this->overbookedAppointments->add($appointment);
            } else {
                $appointments->add($appointment);
            }
        }

        return $appointments;
    }

    private function getSpotAndUpdateAppointmentSpotId(PestRoutesAppointment $appointment): PestRoutesSpot|null
    {
        if (empty($appointment->spotId)) {
            $spot = $this->getSpotByAppointmentId($appointment->id);

            if ($spot !== null) {
                $this->appointmentsDataProcessor->assignAppointment(
                    $this->officeId,
                    $spot->routeId,
                    $appointment->id,
                    $spot->id,
                );
            }

            return $spot;
        }

        return $this->getSpotById($appointment->spotId);
    }

    private function getSpotByAppointmentId(int $appointmentId): PestRoutesSpot|null
    {
        return $this->spots->first(
            fn (PestRoutesSpot $spot) => $this->spotMatchesAppointmentId($spot, $appointmentId),
        );
    }

    private function spotMatchesAppointmentId(PestRoutesSpot $spot, int $appointmentId): bool
    {
        $matchesCurrentAppointmentId = $spot->currentAppointmentId === $appointmentId;
        $matchesAssignedAppointmentId = !empty($spot->appointmentIds) && in_array($appointmentId, $spot->appointmentIds);

        return $matchesCurrentAppointmentId || $matchesAssignedAppointmentId;
    }

    private function getRouteBreaks(int $routeId): Collection
    {
        $routeSpots = $this->spots->filter(
            fn (PestRoutesSpot $spot) => $spot->capacity === 0
                && $this->hasBlockReasonMarker($spot, PestRoutesBlockedSpotReasons::BREAK_MARKER)
                && $spot->routeId === $routeId
        );

        return $routeSpots->map(function (PestRoutesSpot $spot) {
            if ($this->hasBlockReasonMarker($spot, PestRoutesBlockedSpotReasons::LUNCH_MARKER)) {
                $break = new Lunch($spot->id, $spot->blockReason);
                $breakDuration = DomainContext::getLunchDuration();
            } else {
                $break = new WorkBreak($spot->id, $spot->blockReason);
                $breakDuration = DomainContext::getWorkBreakDuration();
            }

            $breakEnd = Carbon::instance($spot->start)->addMinutes($breakDuration);

            return $break
                ->setRouteId($spot->routeId)
                ->setTimeWindow(new TimeWindow(
                    Carbon::instance($spot->start),
                    $breakEnd,
                ))
                ->setExpectedArrival(new TimeWindow(
                    Carbon::instance($spot->start),
                    Carbon::instance($spot->end),
                ));
        });
    }

    private function hasBlockReasonMarker(PestRoutesSpot $spot, string $marker): bool
    {
        return !is_null($spot->blockReason) && stripos($spot->blockReason, $marker) !== false;
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

    /**
     * Returns optimization engine based on feature flag
     *
     * @return OptimizationEngine
     */
    private function selectOptimizationEngine(): OptimizationEngine
    {
        $engineFlag = $this->featureFlagService->getFeatureFlagStringValueForOffice(
            $this->officeId,
            self::ROUTE_OPTIMIZATION_ENGINE_FEATURE_FLAG,
        );

        return OptimizationEngine::from($engineFlag);
    }

    private function resolveSkipBuildFeature(): void
    {
        $this->isSkipBuildEnabled = $this->featureFlagService->isFeatureEnabledForOffice(
            $this->officeId,
            self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG,
        );
    }
}
