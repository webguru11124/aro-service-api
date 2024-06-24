<?php

declare(strict_types=1);

namespace App\Domain\Calendar\ValueObjects;

use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Enums\WeekNumInMonth;
use Illuminate\Support\Collection;

class WeeklyOccurrence
{
    /**
     * @param Collection<WeekDay> $weekDays
     * @param WeekNumInMonth|null $weekNumInMonth
     */
    public function __construct(
        public readonly Collection $weekDays,
        public readonly WeekNumInMonth|null $weekNumInMonth = null
    ) {
    }

    /**
     * @return Collection
     */
    public function getOrderedWeekDays(): Collection
    {
        $order = WeekDay::getOrder();

        return $this->weekDays->sort(
            fn (WeekDay $left, WeekDay $right) => array_search($left, $order) <=> array_search($right, $order)
        )->values();
    }

    /**
     * Returns true if the occurrence is on the given week day
     *
     * @param string $weekDayName
     *
     * @return bool
     */
    public function isOn(string $weekDayName): bool
    {
        return !empty($this->weekDays->first(
            fn (WeekDay $weekDay) => strtolower($weekDay->value) === strtolower($weekDayName)
        ));
    }
}
