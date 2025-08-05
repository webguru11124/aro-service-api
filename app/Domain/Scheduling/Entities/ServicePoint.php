<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;

class ServicePoint
{
    public const MAX_PRIORITY = 100;

    /** @var int[] */
    private array $nearestServiceIds;

    public function __construct(
        private int $id,
        private int $referenceId,
        private Coordinate $location,
        private int $priority,
        private int|null $preferredEmployeeId = null,
        private bool $reserved = false,
    ) {
        $this->nearestServiceIds = [];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->location;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return bool
     */
    public function isHighPriority(): bool
    {
        return $this->priority >= self::MAX_PRIORITY;
    }

    /**
     * @return array|int[]
     */
    public function getNearestServiceIds(): array
    {
        return $this->nearestServiceIds;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function addNearestServiceId(int $id): self
    {
        $this->nearestServiceIds[] = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getReferenceId(): int
    {
        return $this->referenceId;
    }

    /**
     * @return bool
     */
    public function isReserved(): bool
    {
        return $this->reserved;
    }

    /**
     * @return void
     */
    public function reserve(): void
    {
        $this->reserved = true;
    }

    /**
     * @param ServicePoint $nextServicePoint
     *
     * @return float
     */
    public function getWeightToNextPoint(ServicePoint $nextServicePoint): float
    {
        $distance = $this->getLocation()->distanceTo($nextServicePoint->getLocation())->getMiles();

        return $distance > 0 ? $nextServicePoint->getPriority() / $distance : $nextServicePoint->getPriority();
    }

    /**
     * @return int|null
     */
    public function getPreferredEmployeeId(): int|null
    {
        return $this->preferredEmployeeId;
    }
}
