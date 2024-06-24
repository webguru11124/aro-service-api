<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\Actions;

use App\Domain\Calendar\Actions\SearchEvents;
use App\Domain\Calendar\Actions\SearchEventsParams;
use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Enums\WeekNumInMonth;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Carbon\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\EventFactory;
use Tests\Tools\Factories\Calendar\OverrideFactory;
use Tests\Tools\TestValue;

class SearchCalendarEventsTest extends TestCase
{
    private const START_DATE = '2023-01-01';
    private const END_DATE = '2023-01-31';
    private const FIRST_EVENT_TITLE = 'First title';
    private const SECOND_EVENT_TITLE = 'Second title';
    private const OVERRIDE_TITLE = 'Override title';

    private SearchEvents $action;
    private CalendarEventRepository|MockInterface $eventRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventRepositoryMock = \Mockery::mock(CalendarEventRepository::class);
        $this->action = new SearchEvents($this->eventRepositoryMock);
    }

    private function setUpEvents(): void
    {
        $overrides = collect([
            OverrideFactory::make([
                'eventId' => TestValue::EVENT_ID,
                'title' => self::OVERRIDE_TITLE,
                'date' => Carbon::parse('2023-01-06'),
            ]),
            OverrideFactory::make([
                'eventId' => TestValue::EVENT_ID,
                'title' => self::FIRST_EVENT_TITLE,
                'date' => Carbon::parse('2023-01-27'),
                'isCanceled' => true,
            ]),
        ]);
        $event1 = EventFactory::make([
            'id' => TestValue::EVENT_ID,
            'officeId' => TestValue::OFFICE_ID,
            'startDate' => Carbon::parse(self::START_DATE),
            'endDate' => Carbon::parse(self::END_DATE),
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::FRIDAY])),
            'title' => self::FIRST_EVENT_TITLE,
            'overrides' => $overrides,
        ]);
        $event2 = EventFactory::make([
            'id' => TestValue::EVENT_ID + 1,
            'officeId' => TestValue::OFFICE_ID,
            'startDate' => Carbon::parse(self::START_DATE),
            'endDate' => Carbon::parse(self::END_DATE),
            'interval' => ScheduleInterval::MONTHLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY]), WeekNumInMonth::FIRST),
            'title' => self::SECOND_EVENT_TITLE,
        ]);

        $this->eventRepositoryMock
            ->shouldReceive('search')
            ->once()
            ->andReturn(collect([$event1, $event2]));
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_returns_proper_events_collection(
        SearchEventsParams $dto,
        array $expectedDates,
        array $expectedTitles
    ): void {
        $this->setUpEvents();

        $result = ($this->action)($dto);

        $dates = $result->map(
            fn (RecurringEvent $event) => $event->getDate()->toDateString()
        )->toArray();

        $titles = $result->map(
            fn (RecurringEvent $event) => $event->getTitle()
        )->toArray();

        $this->assertSame($expectedDates, $dates);
        $this->assertSame($expectedTitles, $titles);
    }

    public static function dataProvider(): iterable
    {
        yield [
            new SearchEventsParams(
                startDate: Carbon::parse(self::START_DATE),
                endDate: Carbon::parse(self::END_DATE),
                officeId: TestValue::OFFICE_ID,
            ),
            [
                '2023-01-01',
                '2023-01-06',
                '2023-01-13',
                '2023-01-20',
                '2023-01-27',
            ],
            [
                self::SECOND_EVENT_TITLE,
                self::OVERRIDE_TITLE,
                self::FIRST_EVENT_TITLE,
                self::FIRST_EVENT_TITLE,
                self::FIRST_EVENT_TITLE,
            ],
        ];

        yield [
            new SearchEventsParams(
                startDate: Carbon::parse(self::START_DATE),
                endDate: Carbon::parse(self::END_DATE),
                officeId: TestValue::OFFICE_ID,
                searchText: 'first'
            ),
            [
                '2023-01-13',
                '2023-01-20',
                '2023-01-27',
            ],
            [
                self::FIRST_EVENT_TITLE,
                self::FIRST_EVENT_TITLE,
                self::FIRST_EVENT_TITLE,
            ],
        ];

        yield [
            new SearchEventsParams(
                startDate: Carbon::parse(self::START_DATE),
                endDate: Carbon::parse(self::END_DATE),
                officeId: TestValue::OFFICE_ID,
                searchText: 'second'
            ),
            [
                '2023-01-01',
            ],
            [
                self::SECOND_EVENT_TITLE,
            ],
        ];

        yield [
            new SearchEventsParams(
                startDate: Carbon::parse(self::START_DATE),
                endDate: Carbon::parse(self::END_DATE),
                officeId: TestValue::OFFICE_ID,
                searchText: 'override'
            ),
            [
                '2023-01-06',
            ],
            [
                self::OVERRIDE_TITLE,
            ],
        ];

        yield [
            new SearchEventsParams(
                startDate: null,
                endDate: Carbon::parse(self::END_DATE),
                officeId: TestValue::OFFICE_ID,
            ),
            [
                '2023-01-27',
                '2023-01-01',
            ],
            [
                self::FIRST_EVENT_TITLE,
                self::SECOND_EVENT_TITLE,
            ],
        ];
    }
}
