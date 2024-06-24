<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\RouteOptimization\Exceptions\UnknownRouteReoptimizationAction;

class ReoptimizationActionFactory
{
    private const REGISTER = [
        LimitBreakTimeFrames::class,
        LimitFirstAppointmentExpectedArrival::class,
        ReduceWorkTimeRange::class,
        ReverseRoute::class,
    ];

    /** @var array<string, AbstractReoptimizationAction> */
    private array $actionsPool = [];

    /**
     * @param class-string $actionClass
     *
     * @return AbstractReoptimizationAction
     */
    public function getAction(string $actionClass): AbstractReoptimizationAction
    {
        if (!in_array($actionClass, self::REGISTER)) {
            throw new UnknownRouteReoptimizationAction();
        }

        if (isset($this->actionsPool[$actionClass])) {
            return $this->actionsPool[$actionClass];
        }

        $this->actionsPool[$actionClass] = app()->make($actionClass);

        return $this->actionsPool[$actionClass];
    }
}
