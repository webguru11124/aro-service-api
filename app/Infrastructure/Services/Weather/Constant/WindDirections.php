<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Weather\Constant;

final class WindDirections
{
    public const NORTH = 'N';
    public const NORTH_NORTH_EAST = 'NNE';
    public const NORTH_EAST = 'NE';
    public const EAST_NORTH_EAST = 'ENE';
    public const EAST = 'E';
    public const EAST_SOUTH_EAST = 'ESE';
    public const SOUTH_EAST = 'SE';
    public const SOUTH_SOUTH_EAST = 'SSE';
    public const SOUTH = 'S';
    public const SOUTH_SOUTH_WEST = 'SSW';
    public const SOUTH_WEST = 'SW';
    public const WEST_SOUTH_WEST = 'WSW';
    public const WEST = 'W';
    public const WEST_NORTH_WEST = 'WNW';
    public const NORTH_WEST = 'NW';
    public const NORTH_NORTH_WEST = 'NNW';

    public const DIRECTIONS = [
        self::NORTH => [
            'id' => 1,
            'cardinal_point' => 'North',
            'abbreviation' => 'N',
            'degrees' => 0.0,
        ],
        self::NORTH_NORTH_EAST => [
            'id' => 2,
            'cardinal_point' => 'North-Northeast',
            'abbreviation' => 'NNE',
            'degrees' => 22.5,
        ],
        self::NORTH_EAST => [
            'id' => 3,
            'cardinal_point' => 'Northeast',
            'abbreviation' => 'NE',
            'degrees' => 45.0,
        ],
        self::EAST_NORTH_EAST => [
            'id' => 4,
            'cardinal_point' => 'East-Northeast',
            'abbreviation' => 'ENE',
            'degrees' => 67.5,
        ],
        self::EAST => [
            'id' => 5,
            'cardinal_point' => 'East',
            'abbreviation' => 'E',
            'degrees' => 90.0,
        ],
        self::EAST_SOUTH_EAST => [
            'id' => 6,
            'cardinal_point' => 'East-Southeast',
            'abbreviation' => 'ESE',
            'degrees' => 112.5,
        ],
        self::SOUTH_EAST => [
            'id' => 7,
            'cardinal_point' => 'Southeast',
            'abbreviation' => 'SE',
            'degrees' => 135.0,
        ],
        self::SOUTH_SOUTH_EAST => [
            'id' => 8,
            'cardinal_point' => 'South-Southeast',
            'abbreviation' => 'SSE',
            'degrees' => 157.5,
        ],
        self::SOUTH => [
            'id' => 9,
            'cardinal_point' => 'South',
            'abbreviation' => 'S',
            'degrees' => 180.0,
        ],
        self::SOUTH_SOUTH_WEST => [
            'id' => 10,
            'cardinal_point' => 'South-Southwest',
            'abbreviation' => 'SSW',
            'degrees' => 202.5,
        ],
        self::SOUTH_WEST => [
            'id' => 11,
            'cardinal_point' => 'Southwest',
            'abbreviation' => 'SW',
            'degrees' => 225.0,
        ],
        self::WEST_SOUTH_WEST => [
            'id' => 12,
            'cardinal_point' => 'West-Southwest',
            'abbreviation' => 'WSW',
            'degrees' => 247.5,
        ],
        self::WEST => [
            'id' => 13,
            'cardinal_point' => 'West',
            'abbreviation' => 'W',
            'degrees' => 270.0,
        ],
        self::WEST_NORTH_WEST => [
            'id' => 14,
            'cardinal_point' => 'West-Northwest',
            'abbreviation' => 'WNW',
            'degrees' => 292.5,
        ],
        self::NORTH_WEST => [
            'id' => 15,
            'cardinal_point' => 'Northwest',
            'abbreviation' => 'NW',
            'degrees' => 315.0,
        ],
        self::NORTH_NORTH_WEST => [
            'id' => 16,
            'cardinal_point' => 'North-Northwest',
            'abbreviation' => 'NNW',
            'degrees' => 337.5,
        ],
    ];
}
