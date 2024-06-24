<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;

class BucketSpotStrategy implements SpotStrategy
{
    /**
     * @return SpotType
     */
    public function getSpotType(): SpotType
    {
        return SpotType::BUCKET;
    }

    /**
     * @param Spot $spot
     *
     * @return string
     */
    public function getWindow(Spot $spot): string
    {
        return $spot->getId() % 2 === 0 ? 'AM' : 'PM';
    }

    /**
     * @return bool
     */
    public function isAroSpot(): bool
    {
        return false;
    }

    /**
     * @param Spot $spot
     *
     * @return Coordinate|null
     */
    public function getPreviousCoordinate(Spot $spot): Coordinate|null
    {
        return $spot->previousCoordinates;
    }

    /**
     * @param Spot $spot
     *
     * @return Coordinate|null
     */
    public function getNextCoordinate(Spot $spot): Coordinate|null
    {
        return $spot->nextCoordinates;
    }
}
