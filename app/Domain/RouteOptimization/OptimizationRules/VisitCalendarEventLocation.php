<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Contracts\Queries\GetEventsOnDateQuery;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Illuminate\Support\Collection;

class VisitCalendarEventLocation extends AbstractGeneralOptimizationRule
{
    private const MORNING_START_TIME = '07:30:00';
    private const MORNING_END_TIME = '08:30:00';

    public function __construct(
        private GetEventsOnDateQuery $eventsQuery,
        private GetOfficesByIdsQuery $officesByIdsQuery,
    ) {
    }

    /**
     * Adjusts route start location based on calendar event
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $events = $this->getCalendarEvents($optimizationState);

        if ($events->isEmpty()) {
            return $this->buildTriggeredExecutionResult();
        }

        $defaultLocation = $this->resolveOfficeLocation($optimizationState->getOffice()->getId());

        foreach ($optimizationState->getRoutes() as $route) {
            foreach ($events as $event) {
                $servicePro = $route->getServicePro();

                if (!$event->isEmployeeInvited($servicePro->getId())) {
                    continue;
                }

                if ($this->intersectWithReservedTime($route, $event)) {
                    continue;
                }

                $location = $event->getLocation() ?? $defaultLocation;
                $route->addWorkEvent(
                    new Meeting(
                        $servicePro->getPersonalSkill()->value,
                        $event->getTitle(),
                        $event->getTimeWindow(),
                        $location
                    )
                );

                if ($this->isMorningEvent($event)) {
                    $route->setupRouteStart(
                        $event->getTimeWindow()->getStartAt(),
                        $location
                    );
                }
            }
        }

        return $this->buildSuccessExecutionResult();
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return Collection<RecurringEvent>
     */
    private function getCalendarEvents(OptimizationState $optimizationState): Collection
    {
        return $this->eventsQuery->get(
            $optimizationState->getOffice()->getId(),
            $optimizationState->getDate()
        );
    }

    private function isMorningEvent(RecurringEvent $event): bool
    {
        $morningEventStartAt = new TimeWindow(
            $event->getDate()->clone()->setTimeFromTimeString(self::MORNING_START_TIME),
            $event->getDate()->clone()->setTimeFromTimeString(self::MORNING_END_TIME),
        );

        return $morningEventStartAt->isDateInTimeWindow($event->getTimeWindow()->getStartAt());
    }

    private function resolveOfficeLocation(int $officeId): Coordinate
    {
        // TODO: Once Office entity will be updated to have Coordinate property,
        // this method can be updated to return Office location directly
        return $this->officesByIdsQuery->get([$officeId])->first()->getLocation();
    }

    private function intersectWithReservedTime(Route $route, RecurringEvent $event): bool
    {
        foreach ($route->getReservedTimes() as $reservedTime) {
            if ($reservedTime->getTimeWindow()->getIntersection($event->getTimeWindow())) {
                return true;
            }
        }

        return false;
    }

    public function name(): string
    {
        return 'Visit Calendar Event Location';
    }

    public function description(): string
    {
        return 'This rule ensures that locations mentioned in calendar events are planned in optimization.';
    }
}
