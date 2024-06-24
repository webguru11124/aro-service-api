<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\Entities;

use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Enums\WeekNumInMonth;
use App\Domain\Calendar\IntervalStrategies\AbstractIntervalStrategy;
use App\Domain\Calendar\IntervalStrategies\IntervalStrategyFactory;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\OverrideFactory;
use Tests\Tools\Factories\Calendar\RecurringEventFactory;
use Tests\Tools\TestValue;

class RecurringEventTest extends TestCase
{
    private string $title;
    private string $description;
    private string $startTime;
    private string $endTime;
    private CarbonTimeZone $timeZone;
    private Coordinate $location;
    private string $meetingLink;
    private array $participantIds;
    private Override $override;
    private Address $address;
    private AbstractIntervalStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->title = $this->faker->title;
        $this->description = $this->faker->text(36);
        $this->startTime = '09:00:00';
        $this->endTime = '09:25:00';
        $this->timeZone = CarbonTimeZone::create('EST');
        $this->location = new Coordinate(35.1234, -65.4532);
        $this->meetingLink = $this->faker->url;
        $this->participantIds = [$this->faker->randomNumber(3), $this->faker->randomNumber(3)];

        $this->address = new Address(
            address: $this->faker->streetAddress,
            city: $this->faker->city,
            state: 'LA',
            zip: '66666'
        );

        $eventEnd = new EventEnd(
            endAfter: EndAfter::NEVER,
        );

        $this->strategy = (new IntervalStrategyFactory())->getIntervalStrategy(
            startDate: Carbon::today(TestValue::TIME_ZONE)->startOfDay(),
            eventEnd: $eventEnd,
            interval: ScheduleInterval::WEEKLY,
            occurrence: new WeeklyOccurrence(collect([WeekDay::FRIDAY]), WeekNumInMonth::FIRST),
        );

        $this->override = OverrideFactory::make([
            'title' => $this->title,
            'description' => $this->description,
            'timeZone' => $this->timeZone,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'location' => $this->location,
            'meetingLink' => $this->meetingLink,
            'address' => $this->address,
        ]);
    }

    /**
     * @test
     */
    public function it_returns_correct_recurring_event(): void
    {
        $date = Carbon::today(TestValue::TIME_ZONE)->startOfDay();
        /** @var RecurringEvent $event */
        $event = RecurringEventFactory::make([
            'id' => TestValue::EVENT_ID,
            'officeId' => TestValue::OFFICE_ID,
            'date' => $date,
            'eventType' => EventType::MEETING,
            'participantIds' => $this->participantIds,
            'strategy' => $this->strategy,
        ]);

        $this->assertEquals(TestValue::EVENT_ID, $event->getIdentity()->getId());
        $this->assertEquals(TestValue::OFFICE_ID, $event->getIdentity()->getOfficeId());
        $this->assertEquals($date, $event->getIdentity()->getDate());
        $this->assertEquals($date, $event->getDate());
        $this->assertTrue($event->isEmployeeInvited($this->participantIds[0]));
        $this->assertEquals(EventType::MEETING, $event->getEventType());
        $this->assertEquals($this->strategy->getOccurrence()->weekDays, $event->getWeeklyOccurrence()->weekDays);
        $this->assertEquals($this->strategy->getOccurrence()->weekNumInMonth->value, $event->getWeekNum());
        $this->assertEquals(WeekDay::FRIDAY->value, $event->getWeekDaysAsCsv());
        $this->assertEquals($this->strategy->getInterval(), $event->getInterval());
        $this->assertEquals($this->strategy->getStartDate(), $event->getStartDate());
        $this->assertEquals($this->strategy->getRepeatEvery(), $event->getRepeatEvery());
        $this->assertNull($event->getEndDate());
        $this->assertEquals($this->participantIds, $event->getParticipantIds()->toArray());
        $this->assertFalse($event->isCanceled());
    }

    /**
     * @test
     */
    public function it_returns_correct_recurring_event_details(): void
    {
        /** @var RecurringEvent $event */
        $event = RecurringEventFactory::make([
            'title' => $this->title,
            'description' => $this->description,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'timeZone' => $this->timeZone,
            'location' => $this->location,
            'meetingLink' => $this->meetingLink,
            'address' => $this->address,
        ]);

        $this->assertEquals($this->title, $event->getTitle());
        $this->assertEquals($this->description, $event->getDescription());
        $this->assertEquals($this->startTime, $event->getStartTime());
        $this->assertEquals($this->endTime, $event->getEndTime());
        $this->assertEquals($this->timeZone, $event->getTimezone());
        $this->assertEquals($this->location, $event->getLocation());
        $this->assertEquals($this->meetingLink, $event->getMeetingLink());
        $this->assertEquals($this->address, $event->getAddress());
        $this->assertEquals('Eastern Standard Time', $event->getTimezoneFullName());
    }

    /**
     * @test
     */
    public function it_returns_correct_recurring_event_details_from_override(): void
    {
        /** @var RecurringEvent $event */
        $event = RecurringEventFactory::make([
            'override' => $this->override,
        ]);
        $overrideEventDetails = $this->override->getEventDetails();

        $this->assertEquals($overrideEventDetails->getTitle(), $event->getTitle());
        $this->assertEquals($overrideEventDetails->getDescription(), $event->getDescription());
        $this->assertEquals($overrideEventDetails->getStartTime(), $event->getStartTime());
        $this->assertEquals($overrideEventDetails->getEndTime(), $event->getEndTime());
        $this->assertEquals($overrideEventDetails->getLocation(), $event->getLocation());
        $this->assertEquals($overrideEventDetails->getAddress(), $event->getAddress());
        $this->assertEquals($this->override->getId(), $event->getOverrideId());
    }
}
