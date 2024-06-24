<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\WeekDay;
use Carbon\CarbonInterface;

trait Weekly
{
    private function getFirstEventDate(): CarbonInterface
    {
        $order = WeekDay::getOrder();
        $startEventDay = WeekDay::tryFrom(strtolower($this->getStartDate()->dayName));
        $startEventDayIndex = array_search($startEventDay, $order);

        $firstEventDate = $this->getStartDate()->clone();
        $weekDays = $this->getOccurrence()->weekDays;

        for ($i = $startEventDayIndex; $i < count($order); $i++) {
            if (in_array(WeekDay::tryFrom(strtolower($firstEventDate->dayName)), $weekDays->all())) {
                return $firstEventDate;
            }
            $firstEventDate->addDay();
        }

        for ($i = 0; $i < $startEventDayIndex; $i++) {
            if (in_array(WeekDay::tryFrom(strtolower($firstEventDate->dayName)), $weekDays->all())) {
                return $firstEventDate;
            }
            $firstEventDate->addDay();
        }

        return $firstEventDate;
    }
}
