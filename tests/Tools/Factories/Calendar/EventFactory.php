<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Calendar;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Enums\WeekNumInMonth;
use App\Domain\Calendar\IntervalStrategies\IntervalStrategyFactory;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class EventFactory extends AbstractFactory
{
    public const TIME_ZONE = 'PST';

    public function single($overrides = []): Event
    {
        $eventEnd = new EventEnd(
            endAfter: isset($overrides['endAfter']) ? $overrides['endAfter'] : EndAfter::DATE,
            date: array_key_exists('endDate', $overrides)
                ? $overrides['endDate']
                : Carbon::today(TestValue::TIME_ZONE)->addMonth()->endOfDay(),
            occurrences: array_key_exists('occurrences', $overrides)
                ? $overrides['occurrences']
                : 3
        );
        $id = $overrides['id'] ?? $this->faker->randomNumber(2);
        $officeId = $overrides['officeId'] ?? $this->faker->randomNumber(2);
        $eventType = $overrides['eventType'] ?? EventType::MEETING;
        $startDate = $overrides['startDate'] ?? Carbon::today(TestValue::TIME_ZONE)->startOfDay();
        $title = $overrides['title'] ?? $this->faker->title();
        $description = $overrides['description'] ?? $this->faker->text(36);
        $startTime = $overrides['startTime'] ?? '08:00:00';
        $endTime = $overrides['endTime'] ?? '08:30:00';
        $timeZone = $overrides['timeZone'] ?? CarbonTimeZone::create(TestValue::TIME_ZONE);
        $location = $overrides['location'] ?? new Coordinate(30.1234, -70.4532);
        $interval = $overrides['interval'] ?? ScheduleInterval::WEEKLY;
        $occurrence = $overrides['weeklyOccurrence'] ?? new WeeklyOccurrence(collect([WeekDay::FRIDAY]), WeekNumInMonth::FIRST);
        $meetingLink = $overrides['meetingLink'] ?? $this->faker->url();
        $address = $overrides['address'] ?? new Address(
            address: $this->faker->streetAddress(),
            city: $this->faker->city(),
            state: 'LA',
            zip: '66666'
        );

        $eventDetails = new EventDetails(
            title: $title,
            description: $description,
            startTime: $startTime,
            endTime: $endTime,
            timeZone: $timeZone,
            location: $location,
            meetingLink: $meetingLink,
            address: $address
        );

        $strategy = $overrides['strategy'] ?? (new IntervalStrategyFactory())->getIntervalStrategy(
            startDate: $startDate,
            eventEnd: $eventEnd,
            interval: $interval,
            occurrence: $occurrence,
        );

        $event = new Event(
            id: $id,
            officeId: $officeId,
            eventType: $eventType,
            intervalStrategy: $strategy,
            eventDetails: $eventDetails,
        );

        if (isset($overrides['overrides'])) {
            $event->addOverrides($overrides['overrides']);
        }

        if (isset($overrides['participantIds'])) {
            $event->addParticipantIds($overrides['participantIds']);
        }

        return $event;
    }
}
