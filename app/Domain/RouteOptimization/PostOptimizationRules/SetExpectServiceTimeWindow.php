<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\SharedKernel\ValueObjects\Duration;

class SetExpectServiceTimeWindow implements PostOptimizationRule
{
    private const TIME_WINDOW_MINUTES = 120; // 2 hour

    /**
     * @return Duration
     */
    public function getTimeWindowMinutes(): Duration
    {
        return Duration::fromMinutes(self::TIME_WINDOW_MINUTES);
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return 'SetExpectServiceTimeWindow';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Set Expect Service Time Window';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule updates anytime appointments to have service time window set to 2 hours in PestRoutes.';
    }
}
