<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Calendar\Factories\EventOverrideFactory;
use App\Domain\Calendar\IntervalStrategies\IntervalStrategyFactory;
use App\Domain\Calendar\SearchCriteria\SearchCalendarEventsCriteria;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Helpers\DateTimeHelper;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostgresCalendarEventRepository extends AbstractPostgresRepository implements CalendarEventRepository
{
    use FetchesData;

    public function __construct(
        private readonly EventOverrideFactory $overrideFactory,
        private readonly IntervalStrategyFactory $intervalStrategyFactory
    ) {
    }

    protected function getQueryBuilder(): Builder
    {
        return parent::getQueryBuilder()->whereNull('t.deleted_at');
    }

    protected function getTableName(): string
    {
        return PostgresDBInfo::OFFICE_DAYS_SCHEDULE;
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): Event
    {
        $rawObject = $this->getQueryBuilder()->where('t.id', '=', $id)->first();

        if ($rawObject === null) {
            throw EventNotFoundException::instance($id);
        }

        return $this->handleSingleRawObject($rawObject);
    }

    /**
     * @param SearchCalendarEventsCriteria $criteria
     *
     * @return Collection<Event>
     */
    public function search(SearchCalendarEventsCriteria $criteria): Collection
    {
        $builder = $this->getQueryBuilder();
        $builder->select([
            't.id',
            't.title',
            't.description',
            't.office_id',
            't.start_date',
            't.end_date',
            't.start_time',
            't.end_time',
            't.time_zone',
            't.location',
            't.interval',
            't.occurrence',
            't.week',
            't.meeting_link',
            't.address',
            't.event_type',
            't.end_after',
            't.repeat_every',
            't.occurrences',
        ]);

        if ($criteria->officeId !== null) {
            $builder = $builder->where('office_id', '=', $criteria->officeId);
        }

        if ($criteria->title !== null) {
            $builder = $builder
                ->leftJoin(PostgresDBInfo::OFFICE_DAYS_SCHEDULE_OVERRIDES . ' as o', 'o.schedule_id', '=', 't.id')
                ->where(function (Builder $query) use ($criteria) {
                    $query
                        ->where('t.title', 'ilike', "%$criteria->title%")
                        ->orWhere('o.title', 'ilike', "%$criteria->title%");
                });
        }

        $allObjects = $builder->get();

        return $this->handleRawObjects($allObjects);
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->markAsDeleted(PostgresDBInfo::OFFICE_DAYS_SCHEDULE_OVERRIDES, [
                ['column' => 'schedule_id', 'value' => $id],
            ]);
            $this->markAsDeleted(PostgresDBInfo::OFFICE_DAYS_PARTICIPANTS, [
                ['column' => 'schedule_id', 'value' => $id],
            ]);
            $this->markAsDeleted($this->getTableName(), [
                ['column' => 'id', 'value' => $id],
            ]);
        });
    }

    /**
     * @inheritdoc
     */
    public function searchByOfficeIdAndDate(int $officeId, CarbonInterface $date): Collection
    {
        $allObjects = $this->getQueryBuilder()
            ->where('office_id', '=', $officeId)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->get();

        return $this
            ->handleRawObjects($allObjects)
            ->filter(fn (Event $event) => $event->isHeldOnDate($date));
    }

    /**
     * @param Event $event
     *
     * @return int
     */
    public function create(Event $event): int
    {
        return $this->getQueryBuilder()
            ->insertGetId([
                'title' => $event->getEventDetails()->getTitle(),
                'description' => $event->getEventDetails()->getDescription(),
                'office_id' => $event->getOfficeId(),
                'start_date' => $event->getStartDate()->toDateString(),
                'end_date' => $event->getEndDate()?->toDateString(),
                'start_time' => $event->getEventDetails()->getStartTime(),
                'end_time' => $event->getEventDetails()->getEndTime(),
                'time_zone' => $event->getEventDetails()->getTimeZone()->getName(),
                'location' => $event->getEventDetails()->getLocation()?->toJson(),
                'interval' => $event->getInterval()->value,
                'occurrence' => $event->getWeeklyOccurrencesAsCsv(),
                'week' => $event->getWeeklyOccurrence()?->weekNumInMonth?->value,
                'meeting_link' => $event->getEventDetails()->getMeetingLink(),
                'address' => $event->getEventDetails()->getAddress()?->toJson(),
                'event_type' => $event->getEventType()->value,
                'end_after' => $event->getEndAfter()->value,
                'repeat_every' => $event->getRepeatEvery(),
                'occurrences' => $event->getMaxOccurrences(),
            ]);
    }

    /**
     * Get next id for event overrides table
     *
     * @return int
     */
    public function getEventOverridesNextId(): int
    {
        $result = DB::select(sprintf(
            "SELECT NEXTVAL(pg_get_serial_sequence('%s', 'id')) as next_id;",
            PostgresDBInfo::OFFICE_DAYS_SCHEDULE_OVERRIDES
        ));

        return $result[0]->next_id;
    }

    /**
     * @inheritDoc
     */
    public function update(Event $event): void
    {
        $this->getQueryBuilder()->where('t.id', '=', $event->getId())->update([
            'title' => $event->getEventDetails()->getTitle(),
            'description' => $event->getEventDetails()->getDescription(),
            'start_date' => $event->getStartDate()->format(DateTimeHelper::DATE_FORMAT),
            'end_date' => $event->getEndDate()?->format(DateTimeHelper::DATE_FORMAT),
            'start_time' => $event->getEventDetails()->getStartTime(),
            'end_time' => $event->getEventDetails()->getEndTime(),
            'time_zone' => $event->getEventDetails()->getTimeZone()->getName(),
            'location' => $event->getEventDetails()->getLocation()?->toJson(),
            'interval' => $event->getInterval()->value,
            'occurrence' => $event->getWeeklyOccurrencesAsCsv(),
            'week' => $event->getWeeklyOccurrence()?->weekNumInMonth?->value,
            'meeting_link' => $event->getEventDetails()->getMeetingLink(),
            'address' => $event->getEventDetails()->getAddress()?->toJson(),
            'event_type' => $event->getEventType()->value,
            'end_after' => $event->getEndAfter()->value,
            'occurrences' => $event->getMaxOccurrences(),
        ]);

        $this->updateParticipants($event);
        $this->updateOverrides($event);
    }

    private function updateOverrides(Event $event): void
    {
        /** @var Collection<Override> $overrides */
        $overrides = $event->getOverrides();

        $existingOverrides = DB::table(PostgresDBInfo::OFFICE_DAYS_SCHEDULE_OVERRIDES)
            ->where('schedule_id', '=', $event->getId())
            ->get();

        foreach ($overrides as $override) {
            $matchingOverrideById = $existingOverrides->firstWhere('id', $override->getId());
            $matchingOverrideByDate = $existingOverrides->firstWhere('date', $override->getDate()->toDateString());

            if ($matchingOverrideById) {
                continue;
            }

            if ($matchingOverrideByDate && $matchingOverrideByDate->id !== $override->getId()) {
                DB::table(PostgresDBInfo::OFFICE_DAYS_SCHEDULE_OVERRIDES)
                    ->where('id', $matchingOverrideByDate->id)
                    ->delete();
            }

            $this->saveOverride($override);
        }
    }

    private function saveOverride(Override $override): int
    {
        return DB::table(PostgresDBInfo::OFFICE_DAYS_SCHEDULE_OVERRIDES)
            ->insertGetId([
                'id' => $override->getId(),
                'schedule_id' => $override->getEventId(),
                'title' => $override->getEventDetails()->getTitle(),
                'description' => $override->getEventDetails()->getDescription(),
                'is_canceled' => $override->isCanceled(),
                'date' => $override->getDate()->format(DateTimeHelper::DATE_FORMAT),
                'start_time' => $override->getEventDetails()->getStartTime(),
                'end_time' => $override->getEventDetails()->getEndTime(),
                'time_zone' => $override->getEventDetails()->getTimeZone()->getName(),
                'location' => $override->getEventDetails()->getLocation()?->toJson(),
                'meeting_link' => $override->getEventDetails()->getMeetingLink(),
                'address' => $override->getEventDetails()->getAddress()?->toJson(),
            ]);
    }

    private function updateParticipants(Event $event): void
    {
        $currentParticipantIds = $event->getParticipantIds()->toArray();

        $existingParticipantIds = DB::table(PostgresDBInfo::OFFICE_DAYS_PARTICIPANTS)
            ->where('schedule_id', '=', $event->getId())
            ->pluck('employee_id')
            ->toArray();

        $idsToAdd = array_diff($currentParticipantIds, $existingParticipantIds);
        $idsToRemove = array_diff($existingParticipantIds, $currentParticipantIds);

        if (!empty($idsToRemove)) {
            DB::table(PostgresDBInfo::OFFICE_DAYS_PARTICIPANTS)
                ->where('schedule_id', '=', $event->getId())
                ->whereIn('employee_id', $idsToRemove)
                ->update(['deleted_at' => Carbon::now()]);
        }

        if (!empty($idsToAdd)) {
            $inserts = array_map(fn (int $id) => [
                'schedule_id' => $event->getId(),
                'employee_id' => $id,
            ], $idsToAdd);

            DB::table(PostgresDBInfo::OFFICE_DAYS_PARTICIPANTS)
                ->insert($inserts);
        }
    }

    /**
     * @param Collection<\stdClass> $rawObjects
     *
     * @return Collection<Event>
     */
    private function handleRawObjects(Collection $rawObjects): Collection
    {
        if ($rawObjects->isEmpty()) {
            return new Collection();
        }

        $events = $this->mapEntities($rawObjects);
        $events = $this->attachOverrides($events);

        return $this->attachParticipants($events);
    }

    private function handleSingleRawObject(\stdClass $rawObject): Event
    {
        $collection = new Collection([$rawObject]);

        return $this->handleRawObjects($collection)->first();
    }

    /**
     * @param \stdClass $databaseObject
     *
     * @return Event
     */
    protected function mapEntity(\stdClass $databaseObject): Event
    {
        $location = !empty($databaseObject->location)
            ? json_decode($databaseObject->location)
            : null;
        $address = empty($databaseObject->address)
            ? null
            : json_decode($databaseObject->address);

        $details = new EventDetails(
            title: $databaseObject->title,
            description: $databaseObject->description,
            startTime: $databaseObject->start_time,
            endTime: $databaseObject->end_time,
            timeZone: CarbonTimeZone::create($databaseObject->time_zone),
            location: $location ? new Coordinate($location->lat, $location->lon) : null,
            meetingLink: $databaseObject->meeting_link,
            address: empty($address)
                ? null
                : new Address(
                    address: $address->address,
                    city: $address->city,
                    state: $address->state,
                    zip: $address->zip
                )
        );

        $startDate = Carbon::parse($databaseObject->start_date, $databaseObject->time_zone)->startOfDay();
        $endDate = $databaseObject->end_date !== null
            ? Carbon::parse($databaseObject->end_date, $databaseObject->time_zone)->endOfDay()
            : null;
        $endAfter = EndAfter::tryFrom($databaseObject->end_after);
        $eventEnd = new EventEnd(
            $endAfter,
            $endDate,
            $databaseObject->occurrences
        );

        return new Event(
            id: $databaseObject->id,
            officeId: $databaseObject->office_id,
            eventType: EventType::tryFrom($databaseObject->event_type),
            intervalStrategy: $this->intervalStrategyFactory->getIntervalStrategy(
                $startDate,
                $eventEnd,
                ScheduleInterval::tryFrom($databaseObject->interval),
                empty($databaseObject->repeat_every) ? 1 : $databaseObject->repeat_every,
                $this->buildWeeklyOccurrence($databaseObject),
            ),
            eventDetails: $details,
        );
    }

    private function buildWeeklyOccurrence(\stdClass $databaseObject): WeeklyOccurrence|null
    {
        return empty($databaseObject->occurrence)
            ? null
            : new WeeklyOccurrence(collect(array_map(
                fn (string $weekday) => WeekDay::tryFrom($weekday),
                explode(',', $databaseObject->occurrence)
            )));
    }

    /**
     * @param Collection<Event> $events
     *
     * @return Collection<Event>
     */
    private function attachOverrides(Collection $events): Collection
    {
        if ($events->isEmpty()) {
            return $events;
        }

        $ids = $events->map(fn (Event $event) => $event->getId())->toArray();
        $allOverrides = $this->searchOverrideByEventIds($ids)
            ->groupBy(fn (Override $override) => $override->getEventId());

        return $events->each(function (Event $event) use ($allOverrides) {
            $eventOverrides = $allOverrides->get($event->getId());

            if ($eventOverrides === null) {
                return;
            }

            $event->addOverrides($eventOverrides);
        });
    }

    /**
     * @param array<int> $eventIds
     */
    private function searchOverrideByEventIds(array $eventIds): Collection
    {
        $overrides = DB::table(PostgresDBInfo::OFFICE_DAYS_SCHEDULE_OVERRIDES)
            ->whereIn('schedule_id', $eventIds)
            ->get();

        return $overrides->map(fn (\stdClass $object) => $this->overrideFactory->createFromRawData($object));
    }

    /**
     * @param Collection<Event> $events
     *
     * @return Collection<Event>
     */
    private function attachParticipants(Collection $events): Collection
    {
        $ids = $events->map(fn (Event $event) => $event->getId())->toArray();

        $rawParticipants = DB::table(PostgresDBInfo::OFFICE_DAYS_PARTICIPANTS)
            ->whereIn('schedule_id', $ids)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('schedule_id');

        return $events->each(function (Event $event) use ($rawParticipants) {
            /** @var Collection<\stdClass>|null $eventParticipants */
            $eventParticipants = $rawParticipants->get($event->getId());

            if ($eventParticipants === null) {
                return;
            }

            $event->addParticipantIds($eventParticipants->pluck('employee_id')->toArray());
        });
    }
}
