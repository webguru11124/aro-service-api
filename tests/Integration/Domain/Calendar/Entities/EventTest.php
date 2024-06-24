<?php

declare(strict_types=1);

namespace Tests\Integration\Domain\Calendar\Entities;

use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\OverrideFactory;
use Tests\Tools\Factories\Calendar\EventFactory;

class EventTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider recurringEventDataProvider
     */
    public function it_returns_recurring_event_correctly(
        ScheduleInterval $interval,
        WeeklyOccurrence|null $occurrence,
        Override|null $override,
        CarbonInterface $date,
        bool $expectEventIsCreated,
        bool $expectEventIsHeld
    ): void {
        /** @var Event $event */
        $event = EventFactory::make([
            'startDate' => Carbon::parse('2024-01-01'),
            'endDate' => Carbon::parse('2024-03-31'),
            'interval' => $interval,
            'weeklyOccurrence' => $occurrence,
            'overrides' => $override ? collect([$override]) : null,
        ]);

        $result = $event->getRecurringEventOnDate($date);

        $this->assertSame($expectEventIsCreated, $result !== null);
        $isHeld = $result !== null && !$result->isCanceled();
        $this->assertSame($expectEventIsHeld, $isHeld);
        if ($expectEventIsCreated) {
            $this->assertSame($date->toDateString(), $result->getDate()->toDateString());
        }
    }

    public static function recurringEventDataProvider(): iterable
    {
        yield [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'override' => null,
            'date' => Carbon::parse('2024-01-01'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => true,
        ];

        yield [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'override' => OverrideFactory::make([
                'date' => Carbon::parse('2024-01-01'),
                'isCanceled' => true,
            ]),
            'date' => Carbon::parse('2024-01-01'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => false,
        ];

        yield [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'override' => OverrideFactory::make([
                'date' => Carbon::parse('2024-01-02'),
            ]),
            'date' => Carbon::parse('2024-01-01'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => true,
        ];

        yield [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'override' => null,
            'date' => Carbon::parse('2024-01-02'),
            'expectEventIsCreated' => false,
            'expectEventIsHeld' => false,
        ];

        yield [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'override' => null,
            'date' => Carbon::parse('2023-12-25'),
            'expectEventIsCreated' => false,
            'expectEventIsHeld' => false,
        ];

        yield [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'override' => null,
            'date' => Carbon::parse('2024-03-25'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => true,
        ];

        yield [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'override' => null,
            'date' => Carbon::parse('2024-04-01'),
            'expectEventIsCreated' => false,
            'expectEventIsHeld' => false,
        ];

        yield [
            'interval' => ScheduleInterval::MONTHLY,
            'weeklyOccurrence' => null,
            'override' => null,
            'date' => Carbon::parse('2024-01-01'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => true,
        ];

        yield [
            'interval' => ScheduleInterval::MONTHLY,
            'weeklyOccurrence' => null,
            'override' => null,
            'date' => Carbon::parse('2023-01-01'),
            'expectEventIsCreated' => false,
            'expectEventIsHeld' => false,
        ];

        yield [
            'interval' => ScheduleInterval::MONTHLY,
            'weeklyOccurrence' => null,
            'override' => null,
            'date' => Carbon::parse('2024-02-05'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => true,
        ];

        yield [
            'interval' => ScheduleInterval::MONTHLY,
            'weeklyOccurrence' => null,
            'override' => null,
            'date' => Carbon::parse('2024-04-01'),
            'expectEventIsCreated' => false,
            'expectEventIsHeld' => false,
        ];

        yield [
            'interval' => ScheduleInterval::MONTHLY,
            'weeklyOccurrence' => null,
            'override' => OverrideFactory::make([
                'date' => Carbon::parse('2024-02-05'),
                'isCanceled' => true,
            ]),
            'date' => Carbon::parse('2024-02-05'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => false,
        ];

        yield [
            'interval' => ScheduleInterval::ONCE,
            'weeklyOccurrence' => null,
            'override' => null,
            'date' => Carbon::parse('2024-01-01'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => true,
        ];

        yield [
            'interval' => ScheduleInterval::ONCE,
            'weeklyOccurrence' => null,
            'override' => null,
            'date' => Carbon::parse('2024-02-05'),
            'expectEventIsCreated' => false,
            'expectEventIsHeld' => false,
        ];

        yield [
            'interval' => ScheduleInterval::ONCE,
            'weeklyOccurrence' => null,
            'override' => OverrideFactory::make([
                'date' => Carbon::parse('2024-01-01'),
                'isCanceled' => true,
            ]),
            'date' => Carbon::parse('2024-01-01'),
            'expectEventIsCreated' => true,
            'expectEventIsHeld' => false,
        ];
    }
}
