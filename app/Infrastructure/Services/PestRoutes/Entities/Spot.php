<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\SpotStrategy;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;
use Carbon\CarbonInterface;

class Spot
{
    public function __construct(
        private SpotStrategy $strategy,
        private int $id,
        private int $officeId,
        private int $routeId,
        private TimeWindow $timeWindow,
        private string $blockReason,
        public readonly Coordinate|null $previousCoordinates,
        public readonly Coordinate|null $nextCoordinates,
    ) {
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
     * @return int
     */
    public function getRouteId(): int
    {
        return $this->routeId;
    }

    /**
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow
    {
        return $this->timeWindow;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->timeWindow->getStartAt()->clone()->startOfDay();
    }

    /**
     * @return string
     */
    public function getBlockReason(): string
    {
        return $this->blockReason;
    }

    /**
     * @return bool
     */
    public function isAroSpot(): bool
    {
        return $this->strategy->isAroSpot();
    }

    /**
     * @return string
     */
    public function getWindow(): string
    {
        return $this->strategy->getWindow($this);
    }

    /**
     * @return SpotType
     */
    public function getType(): SpotType
    {
        return $this->strategy->getSpotType();
    }

    /**
     * @return Coordinate|null
     */
    public function getPreviousCoordinate(): Coordinate|null
    {
        return $this->strategy->getPreviousCoordinate($this);
    }

    /**
     * @return Coordinate|null
     */
    public function getNextCoordinate(): Coordinate|null
    {
        return $this->strategy->getNextCoordinate($this);
    }
}
