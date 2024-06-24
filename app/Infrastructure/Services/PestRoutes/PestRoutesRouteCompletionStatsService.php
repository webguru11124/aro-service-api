<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes;

use App\Domain\Contracts\Services\RouteCompletionStatsService;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\Tracking\Entities\Events\FleetRouteEvent;
use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\Entities\FleetRouteState;
use App\Domain\Tracking\Services\RouteAdherenceCalculator;
use App\Domain\Tracking\ValueObjects\RouteCompletionStats;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PestRoutesRouteCompletionStatsService implements RouteCompletionStatsService
{
    private FleetRouteState $fleetRouteState;

    /** @var Collection<PestRoutesAppointment> */
    private Collection $appointments;

    public function __construct(
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly RouteAdherenceCalculator $routeAdherenceCalculator,
    ) {
    }

    /**
     * Calculates and update completion stats, such as route adherence and number of complete appointments
     *
     * @param FleetRouteState $fleetRouteState
     *
     * @return void
     */
    public function updateCompletionStats(FleetRouteState $fleetRouteState): void
    {
        $this->fleetRouteState = $fleetRouteState;
        $this->resolveAppointments();
        $this->updateStats();
    }

    private function resolveAppointments(): void
    {
        $this->appointments = $this->getAllAppointments(
            $this->fleetRouteState->getOfficeId(),
            $this->fleetRouteState->getFleetRoutes()->map(fn (FleetRoute $route) => $route->getId())->toArray()
        );
    }

    private function updateStats(): void
    {
        foreach ($this->fleetRouteState->getFleetRoutes() as $fleetRoute) {
            $optimizedAppointmentsIds = $this->getOptimizedAppointmentsIds($fleetRoute, $this->appointments);

            $completedAppointmentsIds = $this->appointments
                ->where('routeId', $fleetRoute->getId())
                ->reject(fn (PestRoutesAppointment $appointment) => $appointment->dateCompleted == null)
                ->sortBy(fn (PestRoutesAppointment $appointment) => $appointment->dateCompleted)
                ->pluck('id')
                ->toArray();

            $routeAdherence = $this->routeAdherenceCalculator->calculateRouteAdherence(
                $optimizedAppointmentsIds,
                $completedAppointmentsIds
            );

            $fleetRoute->setCompletionStats(new RouteCompletionStats(
                routeAdherence: $routeAdherence,
                totalAppointments: count($completedAppointmentsIds),
                totalServiceTime: $this->calculateTotalServiceTime($fleetRoute->getId()),
            ));
        }
    }

    private function calculateTotalServiceTime(int $routeId): Duration
    {
        $serviceTime = $this->appointments
            ->where('routeId', $routeId)
            ->reject(fn (PestRoutesAppointment $appointment) => $appointment->dateCompleted == null)
            ->sum(
                fn (PestRoutesAppointment $appointment) => !is_null($appointment->checkIn) && !is_null($appointment->checkOut)
                ? Carbon::instance($appointment->checkIn)->diffInSeconds(Carbon::instance($appointment->checkOut))
                : 0
            );

        return Duration::fromSeconds($serviceTime);
    }

    /**
     * Retrieves all appointments from PestRoutes, for a given office and a set of route IDs.
     *
     * @param int $officeId The ID of the office.
     * @param int[] $routeIds An array of route IDs.
     *
     * @return Collection A collection of appointments
     */
    private function getAllAppointments(int $officeId, array $routeIds): Collection
    {
        return $this->appointmentsDataProcessor->extract($officeId, new SearchAppointmentsParams(
            officeIds: [$officeId],
            routeIds: $routeIds,
        ));
    }

    /**
     * Gets the IDs of optimized appointments, excluding any that have been canceled.
     *
     * @param FleetRoute $fleetRoute The fleet route containing the optimized appointments.
     * @param Collection $allAppointments A collection of all appointments, including canceled ones.
     *
     * @return int[] An array of appointment IDs that are optimized and not canceled.
     */
    private function getOptimizedAppointmentsIds(FleetRoute $fleetRoute, Collection $allAppointments): array
    {
        $optimizedAppointmentsIds = $fleetRoute->getRoute()
            ->map(fn (FleetRouteEvent $event) => $event->getId())
            ->toArray();

        $completedAppointmentIds = $allAppointments
            ->filter(fn (PestRoutesAppointment $appointment) => $appointment->dateCompleted !== null)
            ->pluck('id')
            ->toArray();

        return array_intersect($optimizedAppointmentsIds, $completedAppointmentIds);
    }
}
