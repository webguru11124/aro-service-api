<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\WorkEvent;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Illuminate\Support\Collection;

readonly class ExtraWork implements WorkEvent
{
    private const DESCRIPTION_PREFIX = '\ARO';

    public function __construct(
        private TimeWindow $timeWindow,
        private Coordinate $startLocation,
        private Coordinate $endLocation,
        private Collection $skills,
    ) {
    }

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType
    {
        return WorkEventType::EXTRA_WORK;
    }

    /**
     * @return Coordinate
     */
    public function getStartLocation(): Coordinate
    {
        return $this->startLocation;
    }

    /**
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow
    {
        return $this->timeWindow;
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        return Duration::fromMinutes(
            DomainContext::getRegularAppointmentDuration() + DomainContext::getAppointmentSetUpTime('default')
        );
    }

    /**
     * @return string
     */
    public function getFormattedDescription(): string
    {
        $estimationArrivalHour = $this->getTimeWindow()->getStartAt()->clone()->addMinute()->hour;
        $estimationArrivalHour = max(10, $estimationArrivalHour);
        $estimationArrivalHour = min(16, $estimationArrivalHour);
        $time = [
            $estimationArrivalHour - 2,
            $estimationArrivalHour + 2,
        ];

        return sprintf(
            '%s {"from": [%g, %g], "to": [%g, %g], "skills": ["%s"], "time": [%s]}',
            self::DESCRIPTION_PREFIX,
            round($this->getStartLocation()->getLongitude(), 4),
            round($this->getStartLocation()->getLatitude(), 4),
            round($this->getEndLocation()->getLongitude(), 4),
            round($this->getEndLocation()->getLatitude(), 4),
            implode('", "', $this->getSkills()->map(fn (Skill $skill) => $skill->getLiteral())->toArray()),
            implode(', ', $time),
        );
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getType()->value;
    }

    public function getEndLocation(): Coordinate
    {
        return $this->endLocation;
    }

    /**
     * @return Collection<int|string, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }
}
