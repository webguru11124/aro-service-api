<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use App\Domain\Scheduling\ValueObjects\SchedulingStats;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use App\Domain\Scheduling\ValueObjects\ResignedTechAssignment;

class SchedulingState
{
    /** @var Collection<PendingService> */
    private Collection $pendingServices;

    /** @var Collection<ScheduledRoute> */
    private Collection $scheduledRoutes;

    /** @var int[] $allActiveEmployeeIds */
    private array $allActiveEmployeeIds = [];

    private int $capacityBeforeScheduling = 0;

    public function __construct(
        private readonly int $id,
        private readonly CarbonInterface $date,
        private readonly Office $office,
    ) {
        $this->pendingServices = new Collection();
        $this->scheduledRoutes = new Collection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return Office
     */
    public function getOffice(): Office
    {
        return $this->office;
    }

    /**
     * @param Collection<PendingService> $pendingServices
     *
     * @return SchedulingState
     */
    public function addPendingServices(Collection $pendingServices): self
    {
        $this->pendingServices = $this->pendingServices->merge($pendingServices);

        return $this;
    }

    /**
     * @param Collection<ScheduledRoute> $scheduledRoutes
     *
     * @return $this
     */
    public function addScheduledRoutes(Collection $scheduledRoutes): self
    {
        $this->scheduledRoutes = $this->scheduledRoutes->merge($scheduledRoutes);

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
     * @return Collection<ScheduledRoute>
     */
    public function getScheduledRoutes(): Collection
    {
        return $this->scheduledRoutes;
    }

    /**
     * @return int
     */
    public function getTotalCapacity(): int
    {
        return $this->getScheduledRoutes()->sum(
            fn (ScheduledRoute $scheduledRoute) => $scheduledRoute->getCapacity()
        );
    }

    /**
     * It assigns the services to the scheduled routes
     *
     * @param Collection<ClusterOfServices> $clusters
     *
     * @return void
     */
    public function assignServicesFromClusters(Collection $clusters): void
    {
        /** @var ScheduledRoute $route */
        foreach ($this->getScheduledRoutes() as $route) {
            /** @var ClusterOfServices $cluster */
            $cluster = $clusters->first(
                fn (ClusterOfServices $cluster) => $cluster->getId() === $route->getId()
            );

            if ($cluster->getServicesCount() === 0) {
                continue;
            }

            $serviceRefIds = $cluster->getServices()->map(
                fn (ServicePoint $servicePoint) => $servicePoint->getReferenceId()
            );

            foreach ($this->getPendingServices() as $index => $pendingService) {
                if (!$serviceRefIds->contains($pendingService->getSubscriptionId())) {
                    continue;
                }

                $route->addPendingService($pendingService);
                $this->getPendingServices()->forget([$index]);
                $this->removeRescheduledAppointmentsFromOtherRoutes($route->getId(), $pendingService);
            }
        }
    }

    private function removeRescheduledAppointmentsFromOtherRoutes(int $routeId, PendingService $pendingService): void
    {
        if (!$pendingService->isRescheduled() || !$pendingService->getNextAppointment()->getDate()->isSameDay($this->date)) {
            return;
        }

        /** @var ScheduledRoute $route */
        foreach ($this->getScheduledRoutes() as $route) {
            if ($route->getId() === $routeId) {
                continue;
            }

            $route->removeAppointment($pendingService->getNextAppointment()->getId());
        }
    }

    /**
     * It returns the initial clusters of services
     *
     * @return Collection<ClusterOfServices>
     */
    public function getInitialClusters(): Collection
    {
        return $this->getScheduledRoutes()->map(
            fn (ScheduledRoute $scheduledRoute) => $scheduledRoute->buildCluster()
        );
    }

    /**
     * It returns the pending services as service points, filtering out services
     * where the preferred day does not match the given date's day of the week.
     *
     * @return Collection<ServicePoint>
     */
    public function getPendingServicePointsForScheduledDate(): Collection
    {
        $dayOfWeek = $this->date->dayOfWeek;

        return $this->getPendingServices()
            ->filter(function (PendingService $pendingService) use ($dayOfWeek) {
                return is_null($pendingService->getPreferredDay()) || $pendingService->getPreferredDay() === $dayOfWeek;
            })
            ->values()
            ->map(function (PendingService $pendingService, int $index) {
                return new ServicePoint(
                    $index,
                    $pendingService->getSubscriptionId(),
                    $pendingService->getLocation(),
                    $pendingService->getPriority(),
                    $this->resolvePreferredEmployeeId($pendingService->getPreferredEmployeeId()),
                );
            });
    }

    private function resolvePreferredEmployeeId(int|null $employeeId): int|null
    {
        if (!$employeeId) {
            return null;
        }

        $isActivePreferredEmployee = $this->isPreferredEmployeeActive($employeeId);

        return $isActivePreferredEmployee ? $employeeId : null;
    }

    /**
     * It returns scheduling stats
     *
     * @return SchedulingStats
     */
    public function getStats(): SchedulingStats
    {
        return new SchedulingStats(
            routesCount: $this->getScheduledRoutes()->count(),
            appointmentsCount: $this->getScheduledRoutes()->flatMap->getAppointments()->count(),
            scheduledServicesCount: $this->getScheduledRoutes()->flatMap->getPendingServices()
                ->reject(fn (PendingService $pendingService) => $pendingService->isRescheduled())
                ->count(),
            rescheduledServicesCount: $this->getScheduledRoutes()->flatMap->getPendingServices()
                ->filter(fn (PendingService $pendingService) => $pendingService->isRescheduled())
                ->count(),
            pendingServicesCount: $this->getPendingServices()
                ->reject(fn (PendingService $pendingService) => $pendingService->isRescheduled())
                ->count(),
            pendingRescheduledServices: $this->getPendingServices()
                ->filter(fn (PendingService $pendingService) => $pendingService->isRescheduled())
                ->count(),
            capacityBeforeScheduling: $this->capacityBeforeScheduling,
            capacityAfterScheduling: $this->getTotalCapacity(),
            scheduledHighPriorityServices: $this->getScheduledHighPriorityServicesCount(),
            pendingHighPriorityServices: $this->getPendingHighPriorityServicesCount(),
        );
    }

    private function getScheduledHighPriorityServicesCount(): int
    {
        return $this->getScheduledRoutes()->flatMap->getPendingServices()->filter(
            fn (PendingService $pendingService) => $pendingService->isHighPriority()
        )->count();
    }

    private function getPendingHighPriorityServicesCount(): int
    {
        return $this->getPendingServices()->filter(
            fn (PendingService $pendingService) => $pendingService->isHighPriority()
        )->count();
    }

    /**
     * @return SchedulingState
     */
    public function setMetricsBeforeScheduling(): SchedulingState
    {
        $this->capacityBeforeScheduling = $this->getTotalCapacity();

        return $this;
    }

    /**
     * @return int[]
     */
    public function getAllActiveEmployeeIds(): array
    {
        return $this->allActiveEmployeeIds;
    }

    /**
     * @param int[] $allActiveEmployeeIds
     *
     * @return self
     */
    public function setAllActiveEmployeeIds(array $allActiveEmployeeIds): self
    {
        $this->allActiveEmployeeIds = $allActiveEmployeeIds;

        return $this;
    }

    /**
     * It returns assigned services that have a resigned service pro
     *
     * @return Collection<ResignedTechAssignment>
     */
    public function getResignedTechAssignments(): Collection
    {
        return $this->getScheduledRoutes()
            ->flatMap(fn (ScheduledRoute $scheduledRoute) => $scheduledRoute->getPendingServices())
            ->filter(fn (PendingService $pendingService) => $this->isCustomerPreferredTechResigned($pendingService))
            ->unique()
            ->map(fn (PendingService $pendingService) => new ResignedTechAssignment(
                $pendingService->getCustomer()->getId(),
                $pendingService->getCustomer()->getName(),
                $pendingService->getCustomer()->getEmail(),
                $pendingService->getSubscriptionId(),
                $pendingService->getPreferredEmployeeId(),
            ));
    }

    private function isCustomerPreferredTechResigned(PendingService $pendingService): bool
    {
        return !is_null($pendingService->getPreferredEmployeeId())
            && !$this->isPreferredEmployeeActive($pendingService->getPreferredEmployeeId());
    }

    /**
     * Resets the preferred tech id of the customers with resigned service pro
     *
     * @return self
     */
    public function resetPreferredTechId(): self
    {
        foreach ($this->getScheduledRoutes() as $scheduledRoute) {
            foreach ($scheduledRoute->getPendingServices() as $pendingService) {
                if ($this->isCustomerPreferredTechResigned($pendingService)) {
                    $pendingService->resetPreferredEmployeeId();
                }
            }
        }

        return $this;
    }

    private function isPreferredEmployeeActive(int $preferredEmployeeId): bool
    {
        return in_array($preferredEmployeeId, $this->getAllActiveEmployeeIds());
    }
}
