<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

use Illuminate\Support\Str;

enum RouteType: string
{
    private const PESTROUTE_EXTENDED_TITLE = 'extended';
    private const PESTROUTE_SHORT_TITLE = 'short';
    private const PESTROUTE_REGULAR_TITLE = 'regular';

    case REGULAR_ROUTE = 'Regular Route';
    case EXTENDED_ROUTE = 'Extended Route';
    case SHORT_ROUTE = 'Short Route';
    case UNKNOWN = 'Unknown';

    /**
     * It returns the RouteType from a string
     *
     * @param string $value
     *
     * @return self
     */
    public static function fromString(string $value): self
    {
        return match (true) {
            self::isShortRoute($value) => self::SHORT_ROUTE,
            self::isExtendedRoute($value) => self::EXTENDED_ROUTE,
            self::isRegularRoute($value) => self::REGULAR_ROUTE,
            default => self::UNKNOWN,
        };
    }

    private static function isExtendedRoute(string $routeTitle): bool
    {
        return Str::contains(Str::lower($routeTitle), Str::lower(self::PESTROUTE_EXTENDED_TITLE));
    }

    private static function isShortRoute(string $routeTitle): bool
    {
        return Str::contains(Str::lower($routeTitle), Str::lower(self::PESTROUTE_SHORT_TITLE));
    }

    private static function isRegularRoute(string $routeTitle): bool
    {
        return Str::contains(Str::lower($routeTitle), Str::lower(self::PESTROUTE_REGULAR_TITLE));
    }
}
