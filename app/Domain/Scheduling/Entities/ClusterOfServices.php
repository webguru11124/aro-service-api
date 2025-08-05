<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Distance;
use Illuminate\Support\Collection;

class ClusterOfServices
{
    /** @var Collection<ServicePoint> */
    private Collection $servicePoints;

    public function __construct(
        private int $id,
        private int $capacity,
        private Coordinate $centroid,
        private int|null $employeeId = null,
    ) {
        $this->servicePoints = collect();
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
    public function getCapacity(): int
    {
        return $this->capacity;
    }

    /**
     * @return Collection<ServicePoint>
     */
    public function getServices(): Collection
    {
        return $this->servicePoints;
    }

    /**
     * @return int
     */
    public function getServicesCount(): int
    {
        return $this->servicePoints->count();
    }

    /**
     * Adds a service to the cluster
     *
     * @param ServicePoint $servicePoint
     *
     * @return void
     */
    public function addService(ServicePoint $servicePoint): void
    {
        $servicePoint->reserve();
        $this->servicePoints->push($servicePoint);
    }

    /**
     * @param ServicePoint $servicePoint
     *
     * @return Distance
     */
    public function getDistanceToServicePoint(ServicePoint $servicePoint): Distance
    {
        return $this->centroid->distanceTo($servicePoint->getLocation());
    }

    /**
     * It checks if the cluster can handle the service based on capacity, skills and user preferences
     *
     * @param ServicePoint $servicePoint
     *
     * @return bool
     */
    public function canHandleService(ServicePoint $servicePoint): bool
    {
        if ($this->getServicesCount() >= $this->getCapacity()) {
            return false;
        }

        if (
            $this->employeeId
            && $servicePoint->getPreferredEmployeeId()
            && $this->employeeId !== $servicePoint->getPreferredEmployeeId()
        ) {
            return false;
        }

        // TODO: Implement logic to check skills

        return true;
    }

    /**
     * It checks if the cluster is full of services enough to be schedule them on related route
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->getServicesCount() >= $this->getCapacity();
    }
}
