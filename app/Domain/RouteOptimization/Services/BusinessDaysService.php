<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;

class BusinessDaysService
{
    public const CUT_OFF_HOUR = 13;

    private CarbonInterface $now;
    private CarbonInterface $optimizationDay;
    private CarbonTimeZone $timezone;

    /**
     * @param CarbonInterface $optimizationDay
     *
     * @return bool
     */
    public function needsFirstAppointmentLock(CarbonInterface $optimizationDay): bool
    {
        $this->timezone = $optimizationDay->timezone;
        $this->now = Carbon::now($this->timezone);
        $this->optimizationDay = $optimizationDay;

        if ($this->isBeforeCutoffTime()) { // this should always go first
            return false;
        }
        if ($this->isAHoliday()) {
            return false;
        }
        if ($this->meetsFridayLockCondition()) {
            return true;
        }

        return $this->isTomorrow();
    }

    private function isAHoliday(): bool
    {
        return (
            $this->isNewYearsDay()
            || $this->isDayAfterNewYearsDay()
            || $this->isMemorialDay()
            || $this->isIndependenceDay()
            || $this->isLaborDay()
            || $this->isThanksgivingDay()
            || $this->isDayAfterThanksgiving()
            || $this->isDayBeforeChristmasDay()
            || $this->isChristmasDay()
            || $this->isLastDayOfYear()
        );
    }

    private function isBeforeCutoffTime(): bool
    {
        return $this->now->hour < self::CUT_OFF_HOUR;
    }

    private function isDayAfterNewYearsDay(): bool
    {
        return $this->optimizationDay->month == 1 && $this->optimizationDay->day === 2;
    }

    private function isDayBeforeChristmasDay(): bool
    {
        return $this->optimizationDay->month === 12 && $this->optimizationDay->day === 24;
    }

    private function isChristmasDay(): bool
    {
        return $this->optimizationDay->month === 12 && $this->optimizationDay->day === 25;
    }

    private function isIndependenceDay(): bool
    {
        return $this->optimizationDay->month === 7 && $this->optimizationDay->day === 4;
    }

    private function isLaborDay(): bool
    {
        return $this->optimizationDay->equalTo($this->getFirstMondayOfSeptember());
    }

    private function isLastDayOfYear(): bool
    {
        return $this->optimizationDay->month == 12 && $this->optimizationDay->day === 31;
    }

    private function isNewYearsDay(): bool
    {
        return $this->optimizationDay->month == 1 && $this->optimizationDay->day === 1;
    }

    private function isMemorialDay(): bool
    {
        return $this->optimizationDay->equalTo($this->getLastMondayOfMay());
    }

    private function isThanksgivingDay(): bool
    {
        return $this->optimizationDay->equalTo($this->getFourthThursdayOfNovember());
    }

    private function isTomorrow(): bool
    {
        $tomorrow = $this->now->clone()->addDay();

        return $this->optimizationDay->isSameDay($tomorrow);
    }

    private function isDayAfterThanksgiving(): bool
    {
        $targetDay = $this->getFourthThursdayOfNovember()->addDay();

        return $this->optimizationDay->equalTo($targetDay);
    }

    private function getFirstMondayOfSeptember(): CarbonInterface
    {
        return new Carbon("first Monday of September {$this->optimizationDay->year}", $this->timezone);
    }

    private function getFourthThursdayOfNovember(): CarbonInterface
    {
        return new Carbon("fourth Thursday of November {$this->optimizationDay->year}", $this->timezone);
    }

    private function getLastMondayOfMay(): CarbonInterface
    {
        return new Carbon("last Monday of May {$this->optimizationDay->year}", $this->timezone);
    }

    private function meetsFridayLockCondition(): bool
    {
        if ($this->now->dayOfWeek !== CarbonInterface::FRIDAY) {
            return false;
        }

        return in_array($this->optimizationDay->dayOfWeek, [CarbonInterface::SATURDAY, CarbonInterface::MONDAY]);
    }
}
