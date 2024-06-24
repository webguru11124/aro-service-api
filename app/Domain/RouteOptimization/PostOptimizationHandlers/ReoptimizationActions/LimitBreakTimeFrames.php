<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

class LimitBreakTimeFrames extends AbstractReoptimizationAction
{
    private const MAX_ATTEMPTS = 2;

    protected function attempt(Route $route): Route
    {
        $breaks = $route->getWorkBreaks()->filter(
            fn (WorkBreak $workBreak) => $workBreak->getType() !== WorkEventType::LUNCH
        )->values();

        /** @var WorkBreak $firstBreak */
        $firstBreak = $breaks->get(0);

        /** @var Lunch $lunch */
        $lunch = $route->getLunch()->first();

        /** @var WorkBreak $lastBreak */
        $lastBreak = $breaks->get(1);

        try {
            if ($firstBreak !== null) {
                $seconds = (int) round($firstBreak->getExpectedArrival()->getTotalSeconds() / 2);
                $newTimeWindow = new TimeWindow(
                    $firstBreak->getExpectedArrival()->getStartAt()->clone(),
                    $firstBreak->getExpectedArrival()->getEndAt()->clone()->subSeconds($seconds)
                );
                $firstBreak->setExpectedArrival($newTimeWindow);
            }

            if ($lunch !== null) {
                $seconds = (int) round($lunch->getExpectedArrival()->getTotalSeconds() / 4);
                $newTimeWindow = new TimeWindow(
                    $lunch->getExpectedArrival()->getStartAt()->clone()->addSeconds($seconds),
                    $lunch->getExpectedArrival()->getEndAt()->clone()->subSeconds($seconds)
                );
                $lunch->setExpectedArrival($newTimeWindow);
            }

            if ($lastBreak !== null) {
                $seconds = (int) round($lastBreak->getExpectedArrival()->getTotalSeconds() / 2);
                $newTimeWindow = new TimeWindow(
                    $lastBreak->getExpectedArrival()->getStartAt()->clone()->addSeconds($seconds),
                    $lastBreak->getExpectedArrival()->getEndAt()->clone()
                );
                $lastBreak->setExpectedArrival($newTimeWindow);
            }
        } catch (InvalidTimeWindowException) {
        }

        $route = $this->removeInconsistentBreaks($route);

        return $this->optimizeRoute($route);
    }

    protected function getMaxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    protected function name(): string
    {
        return 'Limit Break Time Frames';
    }
}
