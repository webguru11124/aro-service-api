<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SetStaticTimeWindows implements PostOptimizationRule
{
    private const RANGES = [
        ['08:00:00', '11:00:00'],
        ['10:00:00', '13:00:00'],
        ['12:00:00', '15:00:00'],
        ['14:00:00', '17:00:00'],
        ['16:00:00', '19:00:00'],
    ];

    /**
     * @param Appointment $appointment
     *
     * @return TimeWindow|null
     * @throws InvalidTimeWindowException
     */
    public function calculateStaticTimeWindow(Appointment $appointment): TimeWindow|null
    {
        $appointmentTimeWindows = new Collection();

        $startTime = $appointment->getTimeWindow()->getStartAt();
        foreach (self::RANGES as $range) {
            if ($this->isAppointmentInRange($appointment->getTimeWindow()->getStartAt(), $range)) {
                $appointmentTimeWindows->add($this->timeWindowFromRange($startTime, $range));
            }
        }

        $sortedAppointmentTimeWindows = $appointmentTimeWindows->sortBy(
            fn (TimeWindow $timeWindow) => $this->getSecondsToTimeWindowCenter($timeWindow, $startTime)
        );

        /** @var TimeWindow $result */
        $result = $sortedAppointmentTimeWindows->first();

        return $result;
    }

    private function getSecondsToTimeWindowCenter(TimeWindow $timeWindow, CarbonInterface $time): int
    {
        $timeWindowSeconds = $timeWindow->getTotalSeconds();

        $center = $timeWindow->getStartAt()->clone()->addSeconds((int) round($timeWindowSeconds / 2));

        return $center->diffInSeconds($time);
    }

    /**
     * @param CarbonInterface $date
     * @param string[] $range
     *
     * @return TimeWindow
     * @throws InvalidTimeWindowException
     */
    private function timeWindowFromRange(CarbonInterface $date, array $range): TimeWindow
    {
        return new TimeWindow(
            Carbon::parse($date->toDateString() . ' ' . $range[0], $date->getTimezone()),
            Carbon::parse($date->toDateString() . ' ' . $range[1], $date->getTimezone())
        );
    }

    /**
     * @param CarbonInterface $startTime
     * @param string[] $range
     *
     * @return bool
     * @throws InvalidTimeWindowException
     */
    private function isAppointmentInRange(CarbonInterface $startTime, array $range): bool
    {
        $timeWindow = $this->withDelta($this->timeWindowFromRange($startTime, $range));

        return $timeWindow->isDateInTimeWindow($startTime);
    }

    private function withDelta(TimeWindow $timeWindow): TimeWindow
    {
        $range = [
            $timeWindow->getStartAt()->toTimeString(),
            $timeWindow->getEndAt()->toTimeString(),
        ];

        if ($range === self::RANGES[0]) {
            $timeWindow = new TimeWindow(
                $timeWindow->getStartAt()->clone()->subMinutes(DomainContext::getTravelTimeToFirstLocation()),
                $timeWindow->getEndAt()
            );
        }

        return $timeWindow;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return 'SetStaticTimeWindows';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Set Static Time Windows';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule updates appointments on a route to have expected arrival time, specifically: 8am - 11am, 10am - 1pm, 12pm - 3pm, 2pm - 5pm and 4pm - 7pm';
    }
}
