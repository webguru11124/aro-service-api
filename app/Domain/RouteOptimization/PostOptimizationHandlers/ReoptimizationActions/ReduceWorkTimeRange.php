<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

class ReduceWorkTimeRange extends AbstractReoptimizationAction
{
    private const MAX_ATTEMPTS = 1;

    protected function attempt(Route $route): Route
    {
        if ($this->isSkipped($route)) {
            return $route;
        }

        $servicePro = $route->getServicePro();

        $totalWaitingDuration = $route->getTotalWaiting();
        $workEnd = $route->getEndLocation()
            ->getTimeWindow()
            ->getEndAt()
            ->clone()
            ->subSeconds((int) round($totalWaitingDuration->getTotalSeconds() / 2));

        $oldTimeWindow = $servicePro->getWorkingHours();

        $route->setTimeWindow(new TimeWindow(
            $oldTimeWindow->getStartAt(),
            $workEnd
        ));

        $route = $this->removeInconsistentBreaks($route);

        return $this->optimizeRoute($route);
    }

    private function isSkipped(Route $route): bool
    {
        return $route->getReservedTimes()->isNotEmpty();
    }

    protected function getMaxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    protected function name(): string
    {
        return 'Reduce Work Time Range';
    }
}
