<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\BusinessRules;

class IncreaseTravelSpeed implements BusinessRule
{
    private const SPEED_FACTOR_INCREASE_VALUE = .01;

    /**
     * @return float
     */
    public function getSpeedFactorIncreaseValue(): float
    {
        return self::SPEED_FACTOR_INCREASE_VALUE;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return 'IncreaseTravelSpeed';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Increase Travel Speed';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Increases the speed factor by ' . self::SPEED_FACTOR_INCREASE_VALUE . ' for all routes.';
    }
}
