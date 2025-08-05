<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Weather\Helpers;

use App\Infrastructure\Services\Weather\Constant\WindDirections;

class WindDirectionHelper
{
    private const DIRECTIONS = [
        WindDirections::NORTH,
        WindDirections::NORTH_EAST,
        WindDirections::EAST,
        WindDirections::SOUTH_EAST,
        WindDirections::SOUTH,
        WindDirections::SOUTH_WEST,
        WindDirections::WEST,
        WindDirections::NORTH_WEST,
    ];

    /**
     * Returns text representation of wind direction provided in degrees
     *
     * @param int $degrees
     *
     * @return string
     */
    public static function getTextDirection(int $degrees): string
    {
        $directions = $degrees < 0 ? self::getDirectionsForNegativeDegrees() : self::DIRECTIONS;
        $degrees = abs($degrees);

        $directionIndex = $degrees * count(self::DIRECTIONS) / 360;
        $directionIndex = round($directionIndex);
        $directionIndex = ($directionIndex + count(self::DIRECTIONS)) % count(self::DIRECTIONS);

        return $directions[$directionIndex];
    }

    /**
     * @return array<string>
     */
    private static function getDirectionsForNegativeDegrees(): array
    {
        $directions = self::DIRECTIONS;
        $northDirection = array_shift($directions);
        $directions = array_reverse($directions);
        array_unshift($directions, $northDirection);

        return $directions;
    }
}
