<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\Scheduling\ValueObjects\RouteCapacity;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\Carbon;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ScheduledRoute
{
    /** @var Collection<Appointment>  */
    private Collection $appointments;
    /** @var Collection<PendingService> */
    private Collection $pendingServices;

    private const CAPACITY_THRESHOLD_DAYS_3 = 3;
    private const CAPACITY_THRESHOLD_DAYS_2 = 2;
    private const CAPACITY_REDUCTION_FACTOR_1 = 0.8;
    private const CAPACITY_REDUCTION_FACTOR_2 = 0.9;
    private const CAPACITY_DEFAULT_FACTOR = 1.0;

    public function __construct(
        private int $id,
        private int $officeId,
        private CarbonInterface $date,
        private ServicePro $servicePro,
        private RouteType $routeType,
        private int $actualCapacityCount,
    ) {
        $this->appointments = new Collection();
        $this->pendingServices = new Collection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return ServicePro
     */
    public function getServicePro(): ServicePro
    {
        return $this->servicePro;
    }

    /**
     * @param Appointment $appointment
     *
     * @return $this
     */
    public function addAppointment(Appointment $appointment): ScheduledRoute
    {
        $this->appointments->add($appointment);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    /**
     * @param PendingService $pendingService
     *
     * @return $this
     */
    public function addPendingService(PendingService $pendingService): ScheduledRoute
    {
        $this->pendingServices->add($pendingService);

        return $this;
    }

    /**
     * @return Collection<PendingService>
     */
    public function getPendingServices(): Collection
    {
        return $this->pendingServices;
    }

    /**
     * @return RouteType
     */
    public function getRouteType(): RouteType
    {
        return $this->routeType;
    }

    /**
     * @return int
     */
    public function getActualCapacityCount(): int
    {
        return $this->actualCapacityCount;
    }

    /**
     * Calculates the route capacity based on the difference in days between the current date and the service date
     *
     * @return int
     */
    public function getCapacity(): int
    {
        if ($this->servicePro->getSkillsWithoutPersonal()->isEmpty()) {
            return 0;
        }

        $capacity = (int) round($this->getMaxAvailableCapacity()->getValue() * $this->getCapacityFactor());
        $capacity = $capacity - $this->getPendingServices()->count();

        return max($capacity, 0);
    }

    private function getMaxAvailableCapacity(): RouteCapacity
    {
        $eventsDuration = $this->getAppointments()->map(
            fn (Appointment $appointment) => Duration::fromMinutes($appointment->getDuration()->getTotalMinutes())
        );

        return new RouteCapacity(
            $this->routeType,
            $this->getActualCapacityCount(),
            $eventsDuration,
        );
    }

    private function getCapacityFactor(): float
    {
        $currentDate = Carbon::now($this->getDate()->getTimezone())->startOfDay();
        $diffInDays = $currentDate->diffInDays($this->getDate()->copy()->startOfDay());

        if ($diffInDays >= self::CAPACITY_THRESHOLD_DAYS_3) {
            return self::CAPACITY_REDUCTION_FACTOR_1;
        } elseif ($diffInDays === self::CAPACITY_THRESHOLD_DAYS_2) {
            return self::CAPACITY_REDUCTION_FACTOR_2;
        }

        return self::CAPACITY_DEFAULT_FACTOR;
    }

    /**
     *
     * @return ClusterOfServices
     */
    public function buildCluster(): ClusterOfServices
    {
        return new ClusterOfServices(
            $this->getId(),
            $this->getCapacity(),
            $this->getCentroid(),
            $this->getServicePro()->getId(),
        );
    }

    private function getCentroid(): Coordinate
    {
        $locations = $this->getAppointments()->map(fn (Appointment $appointment) => $appointment->getLocation());
        $locations->add($this->getServicePro()->getStartLocation());

        return new Coordinate(
            $locations->avg(fn (Coordinate $location) => $location->getLatitude()),
            $locations->avg(fn (Coordinate $location) => $location->getLongitude())
        );
    }

    /**
     * Removes an appointment from the scheduled route
     *
     * @param int $getId
     *
     * @return void
     */
    public function removeAppointment(int $getId): void
    {
        $this->appointments = $this->getAppointments()->reject(
            fn (Appointment $appointment) => $appointment->getId() === $getId
        );
    }
}
