<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Services\Calendar;

use App\Application\DTO\EventDTO;
use App\Application\Services\Calendar\CalendarService;
use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Exceptions\OverrideOutOfEventRecurrenceException;
use App\Domain\Calendar\IntervalStrategies\IntervalStrategyFactory;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficeEmployeeQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\EmployeeFactory;
use Tests\Tools\TestValue;

class CalendarServiceTest extends TestCase
{
    protected CalendarService $service;
    protected CalendarEventRepository|MockInterface $mockEventRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockEventRepository = Mockery::mock(CalendarEventRepository::class);

        $this->service = new CalendarService(
            $this->mockEventRepository,
            new IntervalStrategyFactory(),
            new PestRoutesOfficeEmployeeQuery(
                Mockery::mock(PestRoutesEmployeesDataProcessor::class)
            ),
        );
    }

    /**
     * @test
     *
     * ::updateCalendarEventOverride
     */
    public function it_should_update_calendar_event_override(): void
    {
        $event = Mockery::mock(Event::class);
        $override = Mockery::mock(Override::class);

        $override->shouldReceive('getDate')
            ->once()
            ->andReturn(Carbon::now());
        $event->shouldReceive('isScheduledOnDate')
            ->once()
            ->andReturnTrue();
        $event->shouldReceive('addOverrides')
            ->once();

        $this->mockEventRepository->shouldReceive('update')
            ->with($event)
            ->once();

        $this->service->updateCalendarEventOverride($event, $override);
    }

    /**
     * @test
     *
     * ::updateCalendarEventOverride
     */
    public function it_throws_exception_when_provided_date_is_not_event_recurrence(): void
    {
        $event = Mockery::mock(Event::class);
        $override = Mockery::mock(Override::class);

        $override->shouldReceive('getDate')
            ->once()
            ->andReturn(Carbon::now());
        $event->shouldReceive('isScheduledOnDate')
            ->once()
            ->andReturnfalse();
        $event->shouldReceive('addOverrides')
            ->never();

        $event->shouldReceive('getOfficeId')
            ->never();

        $this->expectException(OverrideOutOfEventRecurrenceException::class);
        $this->service->updateCalendarEventOverride($event, $override);
    }

    /**
     * @test
     *
     * ::updateEventParticipants
     */
    public function it_updates_events_participants(): void
    {
        $mockOfficeEmployeesQuery = Mockery::mock(PestRoutesOfficeEmployeeQuery::class);
        $mockOfficeEmployeesQuery
            ->shouldReceive('find')
            ->with(TestValue::OFFICE_ID)
            ->andReturn(collect([
                EmployeeFactory::make(['id' => TestValue::EMPLOYEE_ID]),
            ]));

        $event = Mockery::mock(Event::class);
        $newParticipantIds = [TestValue::EMPLOYEE_ID, TestValue::EMPLOYEE_ID + 1];

        $event->shouldReceive('addParticipantIds')
            ->with([TestValue::EMPLOYEE_ID])
            ->once();

        $event->shouldReceive('getOfficeId')
            ->once()
            ->andReturn(TestValue::OFFICE_ID);

        $this->mockEventRepository
            ->shouldReceive('update')
            ->once()
            ->with($event);

        $this->service = new CalendarService(
            $this->mockEventRepository,
            new IntervalStrategyFactory(),
            $mockOfficeEmployeesQuery,
        );

        $this->service->updateEventParticipants($event, $newParticipantIds);
    }

    /**
     * @test
     *
     * ::deleteEventParticipants
     */
    public function it_deletes_event_participants(): void
    {
        $event = Mockery::mock(Event::class);
        $participantIds = [1, 2];

        $event->shouldReceive('removeParticipantIds')
            ->once()
            ->andReturn($event);

        $this->mockEventRepository->shouldReceive('update')
            ->once();

        $this->service->deleteEventParticipants($event, ...$participantIds);
    }

    /**
     * @test
     *
     * ::createEvent
     */
    public function it_creates_event(): void
    {
        $dto = new EventDTO(
            officeId: TestValue::OFFICE_ID,
            title: $this->faker->title,
            description: $this->faker->word,
            startTime: '10:00:00',
            endTime: '10:30:00',
            timeZone: CarbonTimeZone::instance(TestValue::TIME_ZONE),
            location: null,
            startDate: Carbon::today(TestValue::TIME_ZONE)->toDateString(),
            eventType: EventType::MEETING->value,
            interval: ScheduleInterval::WEEKLY,
            weekDays: collect([WeekDay::MONDAY]),
            endAfter: EndAfter::NEVER,
            endDate: null,
            meetingLink: null,
            address: null
        );
        $this->mockEventRepository->shouldReceive('getEventOverridesNextId')
            ->once()
            ->andReturn(TestValue::EVENT_ID);
        $this->mockEventRepository->shouldReceive('create')
            ->once()
            ->andReturn(TestValue::EVENT_ID);

        $eventId = $this->service->createEvent($dto);

        $this->assertEquals(TestValue::EVENT_ID, $eventId);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->service,
            $this->mockEventRepository,
        );
    }
}
