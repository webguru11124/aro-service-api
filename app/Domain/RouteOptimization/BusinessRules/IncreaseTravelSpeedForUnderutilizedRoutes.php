<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\BusinessRules;

class IncreaseTravelSpeedForUnderutilizedRoutes implements BusinessRule
{
    private const SPEED_FACTOR_INCREASE_VALUE = .02;
    private const UNDER_UTILIZATION_PERCENT = 65;

    public function getSpeedFactorIncreaseValue(): float
    {
        return self::SPEED_FACTOR_INCREASE_VALUE;
    }

    public function getUnderUtilizationPercent(): int
    {
        return self::UNDER_UTILIZATION_PERCENT;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return 'IncreaseTravelSpeedForUnderutilizedRoutes';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Increase Travel Speed for Underutilized Routes';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Increases the speed factor for underutilized routes by ' . self::SPEED_FACTOR_INCREASE_VALUE . ' if the route utilization is less than ' . self::UNDER_UTILIZATION_PERCENT . '%';
    }
}
