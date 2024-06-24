<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\ValueObjects;

use Tests\TestCase;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Enums\WeekNumInMonth;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;

class WeeklyOccurrenceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $weekDays = collect([WeekDay::MONDAY, WeekDay::TUESDAY, WeekDay::WEDNESDAY]);
        $weekNumInMonth = WeekNumInMonth::FIRST;
        $weeklyOccurrence = new WeeklyOccurrence(
            weekDays: $weekDays,
            weekNumInMonth: $weekNumInMonth,
        );

        $this->assertSame($weekDays, $weeklyOccurrence->weekDays);
        $this->assertSame($weekNumInMonth, $weeklyOccurrence->weekNumInMonth);
    }

    /**
     * @test
     */
    public function it_can_get_ordered_week_days(): void
    {
        $weekDays = collect([WeekDay::WEDNESDAY, WeekDay::MONDAY, WeekDay::TUESDAY]);
        $weeklyOccurrence = new WeeklyOccurrence(
            weekDays: $weekDays,
        );

        $orderedWeekDays = $weeklyOccurrence->getOrderedWeekDays();

        $this->assertSame(WeekDay::MONDAY, $orderedWeekDays->first());
        $this->assertSame(WeekDay::TUESDAY, $orderedWeekDays->get(1));
        $this->assertSame(WeekDay::WEDNESDAY, $orderedWeekDays->last());
    }

    /**
     * @test
     */
    public function it_can_check_if_week_day_is_on_occurrence(): void
    {
        $weekDays = collect([WeekDay::MONDAY, WeekDay::TUESDAY, WeekDay::WEDNESDAY]);
        $weeklyOccurrence = new WeeklyOccurrence(
            weekDays: $weekDays,
        );

        $this->assertTrue($weeklyOccurrence->isOn(WeekDay::MONDAY->value));
        $this->assertTrue($weeklyOccurrence->isOn(WeekDay::TUESDAY->value));
        $this->assertTrue($weeklyOccurrence->isOn(WeekDay::WEDNESDAY->value));
        $this->assertFalse($weeklyOccurrence->isOn(WeekDay::THURSDAY->value));
    }
}
