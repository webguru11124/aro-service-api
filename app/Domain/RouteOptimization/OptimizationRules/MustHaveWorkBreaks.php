<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MustHaveWorkBreaks extends AbstractGeneralOptimizationRule
{
    public const FIRST_BREAK_ID = 1;
    public const LUNCH_BREAK_ID = 2;
    public const SECOND_BREAK_ID = 3;
    private const BREAK_DESCRIPTION = '15 Min Break';
    private const LUNCH_DESCRIPTION = 'Lunch Break';
    private const LUNCH_BREAK_CAPACITY_THRESHOLD = 5;
    private const SECOND_BREAK_CAPACITY_THRESHOLD = 7;
    private const MIN_CAPACITY_FOR_BREAKS = 3;

    /**
     * Rule to ensure that each service pro has mandatory work breaks
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        foreach ($optimizationState->getRoutes() as $route) {
            $route->removeWorkBreaks();
            $this->addMandatoryBreaksToRoute($route);
        }

        return $this->buildSuccessExecutionResult();
    }

    private function addMandatoryBreaksToRoute(Route $route): void
    {
        $breaks = $this->getBreaks($route);
        $fixedWorkEvents = $route->getReservedTimes()->merge($route->getMeetings());

        foreach ($breaks as $break) {
            if ($this->overlapsFixedEvents($break, $fixedWorkEvents)) {
                continue;
            }

            $route->addWorkEvent($break);
        }
    }

    /**
     * @param WorkBreak $break
     * @param Collection<ReservedTime|Meeting> $fixedWorkEvents
     *
     * @return bool
     */
    private function overlapsFixedEvents(WorkBreak $break, Collection $fixedWorkEvents): bool
    {
        if ($fixedWorkEvents->isEmpty()) {
            return false;
        }

        $breakStart = $break->getExpectedArrival()->getStartAt();
        $breakEnd = $break->getExpectedArrival()->getEndAt();

        foreach ($fixedWorkEvents as $workEvent) {
            $reservedTimeStart = $workEvent->getTimeWindow()->getStartAt();
            $reservedTimeEnd = $workEvent->getTimeWindow()->getEndAt();

            if ($breakStart->between($reservedTimeStart, $reservedTimeEnd)
                || $breakEnd->between($reservedTimeStart, $reservedTimeEnd)
                || $reservedTimeStart->between($breakStart, $breakEnd)
                || $reservedTimeEnd->between($breakStart, $breakEnd)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param CarbonInterface $serviceProStartTime
     * @param int[] $breakTimeWindow
     *
     * @return TimeWindow
     */
    private function getTimeWindow(CarbonInterface $serviceProStartTime, array $breakTimeWindow): TimeWindow
    {
        [$relativeTimeWindowStart, $relativeTimeWindowEnd] = $breakTimeWindow;

        return new TimeWindow(
            Carbon::instance($serviceProStartTime)->addMinutes($relativeTimeWindowStart),
            Carbon::instance($serviceProStartTime)->addMinutes($relativeTimeWindowEnd)
        );
    }

    /**
     * @param Route $route
     *
     * @return WorkBreak[]
     */
    private function getBreaks(Route $route): array
    {
        $workingDayStartAt = $route->getServicePro()->getWorkingHours()->getStartAt();
        $capacity = $route->getCapacity();

        if ($capacity <= self::MIN_CAPACITY_FOR_BREAKS) {
            return [];
        }

        $breaks = [$this->createFirstBreak($workingDayStartAt)];

        if ($capacity > self::LUNCH_BREAK_CAPACITY_THRESHOLD) {
            $breaks[] = $this->createLunch($workingDayStartAt);
        }

        if ($capacity > self::SECOND_BREAK_CAPACITY_THRESHOLD) {
            $breaks[] = $this->createSecondBreak($workingDayStartAt);
        }

        return $breaks;
    }

    private function createFirstBreak(CarbonInterface $workingDayStartAt): WorkBreak
    {
        return $this->createWorkBreak(
            self::FIRST_BREAK_ID,
            $workingDayStartAt,
            DomainContext::getFirstWorkBreakTimeWindow()
        );
    }

    private function createSecondBreak(CarbonInterface $workingDayStartAt): WorkBreak
    {
        return $this->createWorkBreak(
            self::SECOND_BREAK_ID,
            $workingDayStartAt,
            DomainContext::getLastWorkBreakTimeWindow()
        );
    }

    /**
     * @param int $id
     * @param CarbonInterface $workingDayStartAt
     * @param int[] $breakTimeWindow
     *
     * @return WorkBreak
     */
    private function createWorkBreak(int $id, CarbonInterface $workingDayStartAt, array $breakTimeWindow): WorkBreak
    {
        $break = new WorkBreak($id, self::BREAK_DESCRIPTION);
        $break->setExpectedArrival($this->getTimeWindow($workingDayStartAt, $breakTimeWindow));
        $break->setDuration(Duration::fromMinutes(DomainContext::getWorkBreakDuration()));

        return $break;
    }

    private function createLunch(CarbonInterface $workingDayStartAt): Lunch
    {
        $lunch = new Lunch(self::LUNCH_BREAK_ID, self::LUNCH_DESCRIPTION);
        $lunch->setExpectedArrival($this->getTimeWindow($workingDayStartAt, DomainContext::getLunchTimeWindow()));
        $lunch->setDuration(Duration::fromMinutes(DomainContext::getLunchDuration()));

        return $lunch;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Have Work Breaks';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        $workBreakDuration = DomainContext::getWorkBreakDuration();
        $lunchBreakDuration = DomainContext::getLunchDuration();

        return 'This rule ensures that each service pro has mandatory work breaks. Usually work breaks consist of two ' . self::BREAK_DESCRIPTION . " breaks with a duration of $workBreakDuration minutes and one " . self::LUNCH_DESCRIPTION . " with a duration of $lunchBreakDuration minutes.
        \n
        Also note that the breaks must occur within these time windows in the working day:
        \n
        \nFirst Break: between " . round(DomainContext::getFirstWorkBreakTimeWindow()[0] / 60, 1) . ' hours and ' . round(DomainContext::getFirstWorkBreakTimeWindow()[1] / 60, 1) . ' hours after the start of the working day.'
        . "\nLunch Break: between " . round(DomainContext::getLunchTimeWindow()[0] / 60, 1) . ' hours and ' . round(DomainContext::getLunchTimeWindow()[1] / 60, 1) . ' hours after the start of the working day.'
        . "\nSecond Break: between " . round(DomainContext::getLastWorkBreakTimeWindow()[0] / 60, 1) . ' hours and ' . round(DomainContext::getLastWorkBreakTimeWindow()[1] / 60, 1) . ' hours after the start of the working day.';
    }
}
