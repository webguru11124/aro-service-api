<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators;

use Illuminate\Contracts\Container\BindingResolutionException;

class RouteValidatorsRegister
{
    private const ROUTE_VALIDATORS = [
        LongInactivity::class,
        AverageInactivity::class,
        InactivityBeforeFirstAppointment::class,
        TwoBreaksInARow::class,
    ];

    /**
     * @return iterable<RouteValidator>
     * @throws BindingResolutionException
     */
    public function getValidators(): iterable
    {
        foreach (self::ROUTE_VALIDATORS as $handlerClass) {
            yield app()->make($handlerClass);
        }
    }
}
