<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;

interface SpotStrategy
{
    public function getSpotType(): SpotType;

    public function getWindow(Spot $spot): string;

    public function isAroSpot(): bool;

    public function getPreviousCoordinate(Spot $spot): Coordinate|null;

    public function getNextCoordinate(Spot $spot): Coordinate|null;
}
