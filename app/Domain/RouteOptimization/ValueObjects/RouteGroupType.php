<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

use Illuminate\Support\Str;

enum RouteGroupType: string
{
    case REGULAR_ROUTE = 'Regular Route';
    case UNKNOWN = 'Unknown';

    public static function fromString(string $value): RouteGroupType
    {
        return match (true) {
            Str::singular($value) === Str::singular(self::REGULAR_ROUTE->value) => self::REGULAR_ROUTE,
            default => self::UNKNOWN,
        };
    }
}
