<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\Entities;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\IntervalStrategies\AbstractIntervalStrategy;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\Calendar\ValueObjects\RecurringEventId;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use DateTimeZone;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\EventFactory;
use Tests\Tools\Factories\Calendar\OverrideFactory;

class EventTest extends TestCase
{
    private CarbonInterface $scheduledDate;
    private CarbonInterface $updatedDate;
    private CarbonInterface $canceledDate;
    private string $startTime;
    private string $endTime;
    private Carbon $startDate;
    private Carbon $endDate;
    private string $description;
    private CarbonTimeZone $timeZone;
    private Coordinate $location;
    private array $participantIds;
    private string $title;
    private int $eventId;
    private Override $overrideUpdate;
    private Override $overrideCancel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeZone = CarbonTimeZone::create('EST');
        $this->startDate = Carbon::now($this->timeZone)->startOfDay();
        $this->endDate = $this->startDate->clone()->addMonths(3)->endOfDay();
        $this->title = $this->faker->title;
        $this->description = $this->faker->text(36);
        $this->startTime = '09:00:00';
        $this->endTime = '09:25:00';
        $this->location = new Coordinate(35.1234, -65.4532);
        $this->participantIds = [$this->faker->randomNumber(3), $this->faker->randomNumber(3)];
        $this->eventId = $this->faker->randomNumber(2);

        $this->scheduledDate = $this->startDate->clone();
        $this->updatedDate = $this->startDate->clone()->addWeek();
        $this->canceledDate = $this->startDate->clone()->addWeeks(2);

        $this->overrideUpdate = OverrideFactory::make([
            'eventId' => $this->eventId,
            'isCanceled' => false,
            'title' => $this->title,
            'description' => $this->description,
            'timeZone' => $this->timeZone,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'location' => $this->location,
            'date' => $this->updatedDate,
        ]);

        $this->overrideCancel = OverrideFactory::make([
            'title' => $this->title,
            'eventId' => $this->eventId,
            'isCanceled' => true,
            'timeZone' => $this->timeZone,
            'date' => $this->canceledDate,
        ]);
    }

    private function getScheduleOverrides(): array
    {
        return [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'title' => $this->title,
            'description' => $this->description,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'timeZone' => $this->timeZone,
            'location' => $this->location,
            'participantIds' => $this->participantIds,
        ];
    }

    /**
     * @test
     */
    public function it_returns_correct_event(): void
    {
        /** @var Event $event */
        $event = EventFactory::make(array_merge($this->getScheduleOverrides(), [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(
                collect([WeekDay::from(strtolower($this->startDate->dayName))])
            ),
        ]));

        $first = $this->startDate->clone();
        $second = $first->clone()->addWeek();
        $third = $second->clone()->addWeek();

        foreach ([$first, $second, $third] as $day) {
            $result = $event->getRecurringEventOnDate($day);

            $this->assertSame($this->title, $result->getTitle());
            $this->assertSame($this->description, $result->getDescription());
            $this->assertSame($this->startTime, $result->getTimeWindow()->getStartAt()->toTimeString());
            $this->assertSame($this->endTime, $result->getTimeWindow()->getEndAt()->toTimeString());
            $this->assertTrue($day->isSameDay($result->getTimeWindow()->getStartAt()));
            $this->assertTrue($day->isSameDay($result->getTimeWindow()->getEndAt()));
            $this->assertSame($this->location, $result->getLocation());
            $this->assertSame($this->participantIds, $result->getParticipantIds()->toArray());
        }
    }

    /**
     * @test
     *
     * ::getRecurringEventOnDate
     */
    public function it_returns_correct_updated_event(): void
    {
        /** @var Event $event */
        $event = EventFactory::make(array_merge($this->getScheduleOverrides(), [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(
                collect([WeekDay::from(strtolower($this->startDate->dayName))])
            ),
            'overrides' => new Collection([$this->overrideUpdate]),
        ]));

        $result = $event->getRecurringEventOnDate($this->updatedDate);

        $this->assertSame($this->title, $result->getTitle());
        $this->assertSame($this->description, $result->getDescription());
        $this->assertSame($this->startTime, $result->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertSame($this->endTime, $result->getTimeWindow()->getEndAt()->toTimeString());
        $this->assertTrue($this->updatedDate->isSameDay($result->getTimeWindow()->getStartAt()));
        $this->assertTrue($this->updatedDate->isSameDay($result->getTimeWindow()->getEndAt()));
        $this->assertSame($this->location, $result->getLocation());
    }

    /**
     * @test
     *
     * ::getRecurringEventOnDate
     */
    public function it_returns_canceled_event(): void
    {
        /** @var Event $event */
        $event = EventFactory::make(array_merge($this->getScheduleOverrides(), [
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(
                collect([WeekDay::from(strtolower($this->startDate->dayName))])
            ),
            'overrides' => new Collection([$this->overrideCancel]),
        ]));

        $result = $event->getRecurringEventOnDate($this->canceledDate);

        $this->assertSame($this->title, $result->getTitle());
    }

    /**
     * @test
     *
     * ::isHeldOnDate
     */
    public function it_determines_properly_if_scheduled(): void
    {
        $strategyMock = \Mockery::mock(AbstractIntervalStrategy::class);

        /** @var Event $event */
        $event = EventFactory::make(array_merge($this->getScheduleOverrides(), [
            'strategy' => $strategyMock,
            'overrides' => new Collection([$this->overrideUpdate, $this->overrideCancel]),
        ]));

        $strategyMock->shouldReceive('isScheduledOnDate')->times(3)->andReturnTrue();

        $this->assertTrue($event->isHeldOnDate($this->scheduledDate));
        $this->assertTrue($event->isHeldOnDate($this->updatedDate));
        $this->assertFalse($event->isHeldOnDate($this->canceledDate));
    }

    /**
     * @test
     *
     * ::isCanceledOnDate
     */
    public function it_determines_properly_if_canceled(): void
    {
        /** @var Event $event */
        $event = EventFactory::make(array_merge($this->getScheduleOverrides(), [
            'overrides' => new Collection([$this->overrideUpdate, $this->overrideCancel]),
        ]));

        $this->assertFalse($event->isCanceledOnDate($this->scheduledDate));
        $this->assertFalse($event->isCanceledOnDate($this->updatedDate));
        $this->assertTrue($event->isCanceledOnDate($this->canceledDate));
        $this->assertFalse($event->isCanceledOnDate($this->startDate->clone()->subDay()));
        $this->assertFalse($event->isCanceledOnDate($this->endDate->clone()->addDay()));
    }

    /**
     * @test
     *
     * ::isParticipated
     */
    public function it_determines_if_employee_participated(): void
    {
        /** @var Event $event */
        $event = EventFactory::make($this->getScheduleOverrides());

        $this->assertTrue($event->isParticipated($this->participantIds[0]));
        $this->assertTrue($event->isParticipated($this->participantIds[1]));
        $this->assertFalse($event->isParticipated($this->participantIds[1] + 1));
    }

    /**
     * @test
     *
     * ::removeParticipantIds
     */
    public function it_removes_participant_id_correctly(): void
    {
        /** @var Event $event */
        $event = EventFactory::make($this->getScheduleOverrides());

        $event->removeParticipantIds($this->participantIds[0]);

        $this->assertFalse($event->isParticipated($this->participantIds[0]));
    }

    /**
     * @test
     *
     * ::addOverrides
     */
    public function it_adds_override_correctly(): void
    {
        /** @var Event $event */
        $event = EventFactory::make($this->getScheduleOverrides());

        $event->addOverrides(new Collection([$this->overrideUpdate]));

        $this->assertTrue($event->getOverrides()->contains($this->overrideUpdate));
    }

    /**
     * @test
     *
     * ::getWeeklyOccurrencesAsString
     */
    public function it_returns_weekly_occurrences_as_string(): void
    {
        /** @var Event $event */
        $event = EventFactory::make([
            'weeklyOccurrence' => new WeeklyOccurrence(collect([
                WeekDay::MONDAY,
                WeekDay::SUNDAY,
            ])),
        ]);

        $this->assertEquals('monday,sunday', $event->getWeeklyOccurrencesAsCsv());
    }

    /**
     * @test
     */
    public function it_returns_null_for_prev_and_next_occurrence_when_none_exists(): void
    {
        $intervalStrategyMock = $this->createMock(AbstractIntervalStrategy::class);
        $intervalStrategyMock->expects($this->once())
            ->method('getPrevOccurrenceDate')
            ->willReturn(null);
        $intervalStrategyMock->expects($this->once())
            ->method('getNextOccurrenceDate')
            ->willReturn(null);

        $event = new Event(
            1,
            1,
            EventType::MEETING,
            $intervalStrategyMock,
            $this->createMock(EventDetails::class)
        );

        $prevOccurrence = $event->getPrevOccurrence($this->startDate);
        $nextOccurrence = $event->getNextOccurrence($this->startDate);

        $this->assertNull($prevOccurrence);
        $this->assertNull($nextOccurrence);
    }

    /**
     * @test
     */
    public function it_returns_next_and_prev_occurrence_when_one_exists(): void
    {
        $nextDate = $this->startDate->copy()->addDay();
        $prevDate = $this->startDate->copy()->subDay();

        $recurringEventIdMock = Mockery::mock(RecurringEventId::class);
        $recurringEventIdMock->shouldReceive('create')
            ->andReturn($recurringEventIdMock);

        $overrideMock = Mockery::mock(Override::class);
        $overridesCollectionMock = Mockery::mock(Collection::class);
        $overridesCollectionMock->shouldReceive('first')
            ->andReturn($overrideMock);

        $intervalStrategyMock = Mockery::mock(AbstractIntervalStrategy::class);
        $intervalStrategyMock->shouldReceive('getNextOccurrenceDate')->andReturn($nextDate);
        $intervalStrategyMock->shouldReceive('getPrevOccurrenceDate')->andReturn($prevDate);
        $intervalStrategyMock->shouldReceive('getStartDate')->andReturn(Carbon::parse('2024-03-12'));

        $eventDetailsMock = Mockery::mock(EventDetails::class);
        $eventDetailsMock->shouldReceive('getStartTime')->andReturn('00:00:00');
        $eventDetailsMock->shouldReceive('getEndTime')->andReturn('12:00:00');
        $eventDetailsMock->shouldReceive('getTimeZone')->andReturn(CarbonTimeZone::instance(new DateTimeZone('Europe/Paris')));

        $recurringEventIdMock = MOckery::mock(RecurringEventId::class);
        $recurringEventIdMock->shouldReceive('create')
            ->andReturn($recurringEventIdMock);
        $this->instance(RecurringEventId::class, $recurringEventIdMock);

        $event = new Event(
            1,
            1,
            EventType::MEETING,
            $intervalStrategyMock,
            $eventDetailsMock
        );

        $nextOccurrence = $event->getNextOccurrence($this->startDate);
        $prevOccurence = $event->getPrevOccurrence($this->startDate);

        $this->assertInstanceOf(RecurringEvent::class, $nextOccurrence);
        $this->assertInstanceOf(RecurringEvent::class, $prevOccurence);
        $this->assertNotNull($nextOccurrence);
        $this->assertNotNull($prevOccurence);
    }

    /**
     * @test
     */
    public function test_it_returns_expected_values_from_getters(): void
    {
        $eventId = 123;
        $officeId = 456;
        $eventType = EventType::MEETING;
        $startDate = Carbon::now();
        $endDate = $startDate->clone()->addMonth();
        $intervalStrategyMock = Mockery::mock(AbstractIntervalStrategy::class);
        $intervalStrategyMock->shouldReceive('getInterval')->andReturn(ScheduleInterval::MONTHLY);
        $intervalStrategyMock->shouldReceive('getOccurrence')->andReturn(null);
        $intervalStrategyMock->shouldReceive('getStartDate')->andReturn($startDate);
        $intervalStrategyMock->shouldReceive('getEndDate')->andReturn($endDate);
        $intervalStrategyMock->shouldReceive('getEndAfter')->andReturn(EndAfter::DATE);
        $intervalStrategyMock->shouldReceive('getMaxOccurrences')->andReturn(null);
        $eventDetailsMock = Mockery::mock(EventDetails::class);
        $participantIds = [789, 1011];
        $overrides = new Collection([OverrideFactory::make([])]);

        $event = new Event(
            $eventId,
            $officeId,
            $eventType,
            $intervalStrategyMock,
            $eventDetailsMock
        );

        $event->addParticipantIds($participantIds);
        $event->addOverrides($overrides);

        $this->assertEquals($eventId, $event->getId());
        $this->assertEquals($officeId, $event->getOfficeId());
        $this->assertEquals($eventType, $event->getEventType());
        $this->assertEquals(ScheduleInterval::MONTHLY, $event->getInterval());
        $this->assertNull($event->getWeeklyOccurrence());
        $this->assertNull($event->getWeeklyOccurrencesAsCsv());
        $this->assertEquals($startDate, $event->getStartDate());
        $this->assertEquals($endDate, $event->getEndDate());
        $this->assertEquals($event->getEndAfter(), EndAfter::DATE);
        $this->assertNull($event->getMaxOccurrences());
        $this->assertEquals($participantIds, $event->getParticipantIds()->toArray());
        $this->assertEquals($overrides, $event->getOverrides());
        $this->assertEquals($eventDetailsMock, $event->getEventDetails());
    }
}
