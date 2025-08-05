<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Entities;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\Tracking\Entities\Events\FleetRouteEvent;
use App\Domain\Tracking\Entities\Events\PlannedAppointment;
use App\Domain\Tracking\ValueObjects\ConvexPolygon;
use App\Domain\Tracking\ValueObjects\RouteCompletionStats;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use Illuminate\Support\Collection;

class ServicedRoute
{
    private const ROUNDING_PRECISION = 2;
    private const ALLOWED_DELAY_MINUTES = 30;

    /** @var Collection<ScheduledAppointment> */
    private Collection $scheduledAppointments;

    /** @var Collection<FleetRouteEvent> */
    private Collection $plannedEvents;

    public function __construct(
        private int $id,
        private ServicePro $servicePro,
        private RouteStats|null $routeStats = null,
        private string|null $geometry = null,
        private RouteTrackingData|null $trackingData = null,
        private RouteDrivingStats|null $drivingStats = null,
    ) {
        $this->scheduledAppointments = new Collection();
        $this->plannedEvents = new Collection();
    }

    /**
     * Returns ID of the route
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns service pro assigned to the route
     *
     * @return ServicePro
     */
    public function getServicePro(): ServicePro
    {
        return $this->servicePro;
    }

    /**
     * @param ScheduledAppointment $appointment
     *
     * @return void
     */
    public function addScheduledAppointment(ScheduledAppointment $appointment): void
    {
        $this->scheduledAppointments->add($appointment);
    }

    /**
     * Returns route
     *
     * @return Collection<ScheduledAppointment>
     */
    public function getScheduledAppointments(): Collection
    {
        return $this->scheduledAppointments;
    }

    /**
     * @return RouteCompletionStats
     */
    public function getCompletionStats(): RouteCompletionStats
    {
        $plannedAppointmentsIds = $this->getPlannedAppointmentIds();
        $completedAppointmentsIds = $this->getCompleteAppointmentIds();

        $routeAdherence = $this->calculateRouteAdherence(
            $plannedAppointmentsIds,
            $completedAppointmentsIds
        );

        return new RouteCompletionStats(
            routeAdherence: $routeAdherence,
            totalAppointments: count($completedAppointmentsIds),
            totalServiceTime: $this->calculateTotalServiceTime(),
            atRisk: $this->isAtRisk(),
            completionPercentage: $this->getCompletionPercentage(),
        );
    }

    /**
     * @param FleetRouteEvent $event
     *
     * @return void
     */
    public function addPlannedEvent(FleetRouteEvent $event): void
    {
        $this->plannedEvents->add($event);
    }

    /**
     * @return int[]
     */
    private function getPlannedAppointmentIds(): array
    {
        return $this->plannedEvents->filter(
            fn (FleetRouteEvent $event) => $event instanceof PlannedAppointment
        )->map(
            fn (FleetRouteEvent $event) => $event->getId()
        )->toArray();
    }

    /**
     * @return int[]
     */
    private function getCompleteAppointmentIds(): array
    {
        return $this->scheduledAppointments
            ->reject(fn (ScheduledAppointment $appointment) => !$appointment->isComplete())
            ->sortBy(fn (ScheduledAppointment $appointment) => $appointment->getDateComplete())
            ->map(fn (ScheduledAppointment $appointment) => $appointment->getId())
            ->toArray();
    }

    /**
     * Calculates the adherence of a route based on the order of optimized and completed appointments.
     *
     * @param int[] $optimizedAppointmentsOrderedIds An array of optimized appointment IDs in their intended order.
     * @param int[] $completedAppointmentsOrderedIds An array of completed appointment IDs in the order they were completed.
     *
     * @return float|null The calculated route adherence percentage.
     */
    private function calculateRouteAdherence(array $optimizedAppointmentsOrderedIds, array $completedAppointmentsOrderedIds): float|null
    {
        if (empty($optimizedAppointmentsOrderedIds) || empty($completedAppointmentsOrderedIds)) {
            return null;
        }

        $optimizedAppointmentsOrderedIds = array_values($optimizedAppointmentsOrderedIds);
        $completedAppointmentsOrderedIds = array_values($completedAppointmentsOrderedIds);

        $lcsLength = $this->calculateLCSLength($optimizedAppointmentsOrderedIds, $completedAppointmentsOrderedIds);
        $totalAppointments = count($optimizedAppointmentsOrderedIds);

        return ($lcsLength / $totalAppointments) * 100;
    }

    /**
     * Calculates the length of the longest common subsequence (LCS) between two arrays.
     *
     * @param int[] $optimizedIDs The first array of integers.
     * @param int[] $completedIDs The second array of integers.
     *
     * @return int The length of the LCS.
     */
    private function calculateLCSLength(array $optimizedIDs, array $completedIDs): int
    {
        $lengthOptimized = count($optimizedIDs);
        $lengthCompleted = count($completedIDs);
        $LCSMatrix = array_fill(0, $lengthOptimized + 1, array_fill(0, $lengthCompleted + 1, 0));

        for ($i = 0; $i <= $lengthOptimized; $i++) {
            for ($j = 0; $j <= $lengthCompleted; $j++) {
                if ($i == 0 || $j == 0) {
                    $LCSMatrix[$i][$j] = 0;
                } elseif ($optimizedIDs[$i - 1] === $completedIDs[$j - 1]) {
                    $LCSMatrix[$i][$j] = $LCSMatrix[$i - 1][$j - 1] + 1;
                } else {
                    $LCSMatrix[$i][$j] = max($LCSMatrix[$i - 1][$j], $LCSMatrix[$i][$j - 1]);
                }
            }
        }

        return $LCSMatrix[$lengthOptimized][$lengthCompleted];
    }

    private function calculateTotalServiceTime(): Duration
    {
        $serviceTime = $this->scheduledAppointments
            ->filter(fn (ScheduledAppointment $appointment) => $appointment->isComplete())
            ->sum(
                fn (ScheduledAppointment $appointment) => $appointment->getServiceTimeWindow()?->getTotalSeconds()
            );

        return Duration::fromSeconds($serviceTime);
    }

    /**
     * Returns route stats
     *
     * @return RouteStats|null
     */
    public function getRouteStats(): RouteStats|null
    {
        return $this->routeStats;
    }

    /**
     * Returns area of the route
     *
     * @return ConvexPolygon
     */
    public function getArea(): ConvexPolygon
    {
        $points = $this->getScheduledAppointments()->map(fn (ScheduledAppointment $appointment) => $appointment->getLocation());

        return new ConvexPolygon($points);
    }

    /**
     * @return string|null
     */
    public function getGeometry(): string|null
    {
        return $this->geometry;
    }

    /**
     * Return the center of a polygon
     *
     * @return Coordinate|null
     */
    public function getAreaCenter(): Coordinate|null
    {
        $points = $this->getArea()->getVertexes();

        if ($points->isEmpty()) {
            return null;
        }

        $centerLatitude = $points->average(fn (Coordinate $point) => $point->getLatitude());
        $centerLongitude = $points->average(fn (Coordinate $point) => $point->getLongitude());

        return new Coordinate($centerLatitude, $centerLongitude);
    }

    /**
     * Returns true if Service Pro is arriving/completing stops on the route later in the day (>30 mins) than we expected them to.
     *
     * @return bool
     */
    private function isAtRisk(): bool
    {
        $planned = $this->plannedEvents->filter(
            fn (FleetRouteEvent $event) => $event instanceof PlannedAppointment
        )
            ->sortBy(fn (FleetRouteEvent $event) => $event->getTimeWindow()->getStartAt())
            ->mapWithKeys(
                fn (FleetRouteEvent $event) => [$event->getId() => $event->getTimeWindow()]
            );

        $completed = $this->scheduledAppointments
            ->reject(fn (ScheduledAppointment $appointment) => !$appointment->isComplete())
            ->sortBy(fn (ScheduledAppointment $appointment) => $appointment->getDateComplete())
            ->mapWithKeys(
                fn (ScheduledAppointment $appointment) => [$appointment->getId() => $appointment->getServiceTimeWindow()]
            );

        /** @var TimeWindow $timeWindow */
        foreach ($completed as $id => $timeWindow) {
            if (is_null($planned->get($id))) {
                continue;
            }

            /** @var TimeWindow $plannedServiceTime */
            $plannedServiceTime = $planned->get($id);
            $plannedStartAt = $plannedServiceTime->getStartAt()->clone()->addMinutes(self::ALLOWED_DELAY_MINUTES);
            $plannedEndAt = $plannedServiceTime->getEndAt()->clone()->addMinutes(self::ALLOWED_DELAY_MINUTES);

            if (
                $timeWindow->getStartAt()->greaterThan($plannedStartAt)
                || $timeWindow->getEndAt()->greaterThan($plannedEndAt)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param RouteTrackingData|null $trackingData
     *
     * @return void
     */
    public function setTrackingData(RouteTrackingData|null $trackingData): void
    {
        $this->trackingData = $trackingData;
    }

    /**
     * @param RouteDrivingStats|null $drivingStats
     *
     * @return void
     */
    public function setDrivingStats(RouteDrivingStats|null $drivingStats): void
    {
        $this->drivingStats = $drivingStats;
    }

    /**
     * @return RouteTrackingData|null
     */
    public function getTrackingData(): RouteTrackingData|null
    {
        return $this->trackingData;
    }

    /**
     * @return RouteDrivingStats|null
     */
    public function getDrivingStats(): RouteDrivingStats|null
    {
        return $this->drivingStats;
    }

    private function getCompletionPercentage(): float
    {
        $completedAppointments = $this->scheduledAppointments->filter(
            fn (ScheduledAppointment $appointment) => $appointment->isComplete()
        );

        return $this->scheduledAppointments->isNotEmpty()
            ? $completedAppointments->count() / $this->scheduledAppointments->count() * 100
            : 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function getActualStatsAsArray(): array
    {
        $completionStats = $this->getCompletionStats();
        $drivingStats = $this->getDrivingStats();

        $routeAdherence = $completionStats->getRouteAdherence();
        $totalDriveMiles = $drivingStats?->getTotalDriveDistance()->getMiles();
        $averageDriveMiles = $drivingStats?->getAverageDriveDistanceBetweenServices()->getMiles();

        return [
            'total_appointments' => $completionStats->getTotalAppointments(),
            'total_service_time_minutes' => $completionStats->getTotalServiceTime()->getTotalMinutes(),
            'total_drive_time_minutes' => $drivingStats?->getTotalDriveTime()->getTotalMinutes(),
            'total_drive_miles' => is_null($totalDriveMiles) ? null : round($totalDriveMiles, self::ROUNDING_PRECISION),
            'average_drive_time_minutes' => $drivingStats?->getAverageDriveTimeBetweenServices()->getTotalMinutes(),
            'average_drive_miles' => is_null($averageDriveMiles) ? null : round($averageDriveMiles, self::ROUNDING_PRECISION),
            'route_adherence' => is_null($routeAdherence) ? null : round($routeAdherence, self::ROUNDING_PRECISION),
            'at_risk' => $completionStats->isAtRisk(),
            'completion_percentage' => round($completionStats->getCompletionPercentage(), self::ROUNDING_PRECISION),
        ];
    }
}
