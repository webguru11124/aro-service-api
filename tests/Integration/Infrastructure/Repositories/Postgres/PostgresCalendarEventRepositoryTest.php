<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Calendar\Factories\EventOverrideFactory;
use App\Domain\Calendar\IntervalStrategies\IntervalStrategyFactory;
use App\Domain\Calendar\SearchCriteria\SearchCalendarEventsCriteria;
use App\Infrastructure\Repositories\Postgres\PostgresCalendarEventRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\CalendarSeeder;
use Tests\Tools\Factories\Calendar\EventFactory;

class PostgresCalendarEventRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private const TABLE_NAME = 'field_operations.office_days_schedule';
    private const TABLE_NAME_PARTICIPANTS = 'field_operations.office_days_participants';
    private const TABLE_NAME_OVERRIDES = 'field_operations.office_days_schedule_overrides';

    private PostgresCalendarEventRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostgresCalendarEventRepository(
            new EventOverrideFactory(),
            new IntervalStrategyFactory()
        );

        $this->seed([
            CalendarSeeder::class,
        ]);
    }

    /**
     * @test
     *
     * ::search
     */
    public function it_searches_by_office_id(): void
    {
        $officeId = CalendarSeeder::DATA_EVENTS['office_id'][0];

        $criteria = new SearchCalendarEventsCriteria(officeId: $officeId);
        /** @var Collection<Event> $result */
        $result = $this->repository->search($criteria);
        $this->assertEquals(count(CalendarSeeder::DATA_EVENTS['id']), $result->count());

        /** @var Event $firstItem */
        $firstItem = $result->first();
        $eventId = CalendarSeeder::DATA_EVENTS['id'][0];
        $this->assertEquals($eventId, $firstItem->getId());

        $overridesCount = array_count_values(CalendarSeeder::DATA_OVERRIDES['schedule_id'])[$eventId] ?? 0;
        $this->assertEquals($overridesCount, $firstItem->getOverrides()->count());

        /** @var Event $lastItem */
        $lastItem = $result->last();
        $eventId = CalendarSeeder::DATA_EVENTS['id'][1];
        $this->assertEquals($eventId, $lastItem->getId());

        $overridesCount = array_count_values(CalendarSeeder::DATA_OVERRIDES['schedule_id'])[$eventId] ?? 0;
        $this->assertEquals($overridesCount, $lastItem->getOverrides()->count());
    }

    /**
     * @test
     *
     * @dataProvider searchByOfficeIdAndDateDataProvider
     *
     * ::searchByOfficeIdAndDate
     */
    public function it_searches_by_office_id_and_date(int $officeId, CarbonInterface $date, int|null $expectedFoundId): void
    {
        /** @var Collection<Event> $result */
        $result = $this->repository->searchByOfficeIdAndDate($officeId, $date);

        if (is_null($expectedFoundId)) {
            $this->assertEmpty($result);
        } else {
            $this->assertInstanceOf(Event::class, $result->first());
            $this->assertEquals($expectedFoundId, $result->first()->getId());
        }
    }

    public static function searchByOfficeIdAndDateDataProvider(): iterable
    {
        $officeId = CalendarSeeder::DATA_EVENTS['office_id'][0];
        $tz = CalendarSeeder::DATA_EVENTS['time_zone'][0];
        yield [
            'officeId' => $officeId,
            'date' => Carbon::createFromDate(2024, 1, 1, $tz),
            'expectedFoundId' => CalendarSeeder::DATA_EVENTS['id'][0],
        ];
        yield [
            'officeId' => $officeId,
            'date' => Carbon::createFromDate(2024, 2, 26, $tz),
            'expectedFoundId' => CalendarSeeder::DATA_EVENTS['id'][0],
        ];
        yield [
            'officeId' => $officeId,
            'date' => Carbon::createFromDate(2023, 12, 25, $tz),
            'expectedFoundId' => null,
        ];
        yield [
            'officeId' => $officeId,
            'date' => Carbon::createFromDate(2024, 05, 07, $tz),
            'expectedFoundId' => CalendarSeeder::DATA_EVENTS['id'][1],
        ];
        yield [
            'officeId' => $officeId,
            'date' => Carbon::createFromDate(2024, 9, 24, $tz),
            'expectedFoundId' => CalendarSeeder::DATA_EVENTS['id'][1],
        ];
        yield [
            'officeId' => $officeId,
            'date' => Carbon::createFromDate(2024, 7, 24, $tz),
            'expectedFoundId' => null,
        ];
    }

    /**
     * @test
     *
     * ::create
     */
    public function it_creates_schedule(): void
    {
        /** @var Event $event */
        $event = EventFactory::make();

        $this->repository->create($event);

        $this->assertDatabaseHas(self::TABLE_NAME, [
            'office_id' => $event->getOfficeId(),
            'title' => $event->getEventDetails()->getTitle(),
            'description' => $event->getEventDetails()->getDescription(),
            'start_date' => $event->getStartDate()->toDateString(),
            'end_date' => $event->getEndDate()->toDateString(),
            'repeat_every' => $event->getRepeatEvery(),
            'start_time' => $event->getEventDetails()->getStartTime(),
            'end_time' => $event->getEventDetails()->getEndTime(),
            'time_zone' => $event->getEventDetails()->getTimeZone()->getName(),
            'interval' => $event->getInterval()->value,
            'occurrence' => $event->getWeeklyOccurrencesAsCsv(),
            'location' => $event->getEventDetails()->getLocation()->toJson(),
            'meeting_link' => $event->getEventDetails()->getMeetingLink(),
            'address' => $event->getEventDetails()->getAddress()->toJson(),
        ]);
    }

    /**
     * @test
     *
     * ::find
     */
    public function it_finds_single_record_by_id(): void
    {
        $id = CalendarSeeder::DATA_EVENTS['id'][0];

        /** @var Event $result */
        $result = $this->repository->find($id);

        $this->assertInstanceOf(Event::class, $result);
        $this->assertSame($id, $result->getId());
    }

    /**
     * @test
     *
     * ::find
     */
    public function find_method_throws_not_found(): void
    {
        $id = CalendarSeeder::DATA_EVENTS['id'][0] + $this->faker->randomNumber(3);

        $this->expectException(EventNotFoundException::class);
        $this->repository->find($id);
    }

    /**
     * @test
     */
    public function it_throws_not_found_exception_when_attempting_to_find_a_soft_deleted_schedule(): void
    {
        $id = CalendarSeeder::DATA_EVENTS['id'][0];

        $this->assertDatabaseHas(self::TABLE_NAME, [
            'id' => $id,
        ]);

        $this->repository->delete($id);

        $this->assertSoftDeleted(self::TABLE_NAME, [
            'id' => $id,
        ]);

        $this->expectException(EventNotFoundException::class);
        $this->repository->find($id);
    }

    /**
     * @test
     *
     * ::delete
     */
    public function it_deletes_office_days_schedule_by_id(): void
    {
        $eventId = CalendarSeeder::DATA_EVENTS['id'][1];

        $this->assertDatabaseHas(self::TABLE_NAME, [
            'id' => $eventId,
        ]);
        $this->assertDatabaseHas(self::TABLE_NAME_OVERRIDES, [
            'schedule_id' => $eventId,
        ]);
        $this->assertDatabaseHas(self::TABLE_NAME_PARTICIPANTS, [
            'schedule_id' => $eventId,
        ]);

        $this->repository->delete($eventId);

        $this->assertSoftDeleted(self::TABLE_NAME, [
            'id' => $eventId,
        ]);
        $this->assertSoftDeleted(self::TABLE_NAME_OVERRIDES, [
            'schedule_id' => $eventId,
        ]);
        $this->assertSoftDeleted(self::TABLE_NAME_PARTICIPANTS, [
            'schedule_id' => $eventId,
        ]);
    }

    /**
     * @test
     *
     * ::addParticipantIds
     */
    public function it_saves_participant_ids(): void
    {
        $id = CalendarSeeder::DATA_EVENTS['id'][0];

        /** @var Event $event */
        $event = $this->repository->find($id);
        $participantIds = [
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(5),
        ];

        $event->addParticipantIds($participantIds);

        $this->repository->update($event);

        foreach ($participantIds as $participantId) {
            $this->assertDatabaseHas(self::TABLE_NAME_PARTICIPANTS, [
                'schedule_id' => $id,
                'employee_id' => $participantId,
            ]);
        }
    }

    /**
     * @test
     *
     * ::removeParticipantIds
     */
    public function it_removes_participant_ids(): void
    {
        $id = CalendarSeeder::DATA_EVENTS['id'][0];

        /** @var Event $event */
        $event = $this->repository->find($id);
        $participantIds = $event->getParticipantIds();

        $this->assertNotEmpty($participantIds);
        $event->removeParticipantIds(...$event->getParticipantIds()->toArray());
        $this->repository->update($event);

        $event = $this->repository->find($id);
        $this->assertEmpty($event->getParticipantIds());

        foreach ($participantIds as $participantId) {
            $this->assertDatabaseHas(self::TABLE_NAME_PARTICIPANTS, [
                'schedule_id' => $id,
                'employee_id' => $participantId,
            ]);
        }
    }

    /**
     * @test
     *
     * ::getEventOverridesNextId
     */
    public function it_get_schedule_overrides_next_id(): void
    {
        $this->assertIsInt($this->repository->getEventOverridesNextId());
    }
}
