<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Calendar;

use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Enums\WeekNumInMonth;
use App\Domain\Calendar\IntervalStrategies\IntervalStrategyFactory;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\RecurringEventId;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class RecurringEventFactory extends AbstractFactory
{
    public const TIME_ZONE = 'PST';

    public function single($overrides = []): RecurringEvent
    {
        $eventEnd = new EventEnd(
            endAfter: $overrides['endAfter'] ?? EndAfter::DATE,
            date: array_key_exists('endDate', $overrides)
                ? $overrides['endDate']
                : Carbon::today(TestValue::TIME_ZONE)->addMonth()->endOfDay(),
            occurrences: array_key_exists('occurrences', $overrides)
                ? $overrides['occurrences']
                : 3
        );

        $strategy = $overrides['strategy'] ?? (new IntervalStrategyFactory())->getIntervalStrategy(
            startDate: $overrides['startDate'] ?? Carbon::today(TestValue::TIME_ZONE)->startOfDay(),
            eventEnd: $eventEnd,
            interval: $overrides['interval'] ?? ScheduleInterval::WEEKLY,
            repeatEvery: $overrides['repeatEvery'] ?? 1,
            occurrence: $overrides['weeklyOccurrence'] ?? new WeeklyOccurrence(collect([WeekDay::FRIDAY]), WeekNumInMonth::FIRST),
        );

        return new RecurringEvent(
            identity: new RecurringEventId(
                $overrides['id'] ?? $this->faker->randomNumber(2),
                $overrides['officeId'] ?? $this->faker->randomNumber(2),
                $overrides['date'] ?? Carbon::today(TestValue::TIME_ZONE)->startOfDay()
            ),
            eventType: $overrides['eventType'] ?? EventType::MEETING,
            eventDetails: new EventDetails(
                title: $overrides['title'] ?? $this->faker->title(),
                description: $overrides['description'] ?? $this->faker->text(36),
                startTime: $overrides['startTime'] ?? '08:00:00',
                endTime: $overrides['endTime'] ?? '08:30:00',
                timeZone: $overrides['timeZone'] ?? CarbonTimeZone::create(TestValue::TIME_ZONE),
                location: array_key_exists('location', $overrides)
                    ? $overrides['location']
                    : new Coordinate(30.1234, -70.4532),
                meetingLink:  $overrides['meetingLink'] ?? $this->faker->url(),
                address: $overrides['address'] ?? new Address(
                    address: $this->faker->streetAddress(),
                    city: $this->faker->city(),
                    state: 'LA',
                    zip: '66666'
                )
            ),
            participantIds: collect($overrides['participantIds'] ?? []),
            intervalStrategy: $strategy,
            override: $overrides['override'] ?? null
        );
    }
}
