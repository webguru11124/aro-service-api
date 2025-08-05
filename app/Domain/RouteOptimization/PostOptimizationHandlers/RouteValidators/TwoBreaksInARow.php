<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;

class TwoBreaksInARow implements RouteValidator
{
    private const VIOLATION = 'Two work breaks are scheduled one after another';

    /**
     * @param Route $route
     *
     * @return bool
     */
    public function validate(Route $route): bool
    {
        $workEvents = $route->getWorkEvents()->filter(
            fn (WorkEvent $workEvent) => $workEvent instanceof Appointment || $workEvent instanceof WorkBreak
        )->values();

        $prevEvent = null;
        foreach ($workEvents as $workEvent) {
            if ($workEvent instanceof WorkBreak && $prevEvent instanceof WorkBreak) {
                return false;
            }
            $prevEvent = $workEvent;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getViolation(): string
    {
        return self::VIOLATION;
    }
}
