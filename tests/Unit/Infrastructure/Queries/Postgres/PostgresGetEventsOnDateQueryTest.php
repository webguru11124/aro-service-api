<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\Postgres;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Illuminate\Support\Collection;
use Tests\TestCase;
use App\Infrastructure\Queries\Postgres\PostgresGetEventsOnDateQuery;
use Carbon\CarbonImmutable;
use Mockery;
use Mockery\MockInterface;
use Tests\Tools\Factories\Calendar\RecurringEventFactory;
use Tests\Tools\TestValue;

class PostgresGetEventsOnDateQueryTest extends TestCase
{
    private PostgresGetEventsOnDateQuery $query;
    private CalendarEventRepository|MockInterface $eventRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventRepositoryMock = Mockery::mock(CalendarEventRepository::class);
        $this->query = new PostgresGetEventsOnDateQuery($this->eventRepositoryMock);
    }

    /**
     * @test
     */
    public function it_gets_recurring_event(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $date = CarbonImmutable::now();

        $event1 = Mockery::mock(Event::class);
        $event2 = Mockery::mock(Event::class);
        $this->eventRepositoryMock->shouldReceive('search')
            ->once()
            ->withArgs(function ($criteria) use ($officeId) {
                return $criteria->officeId === $officeId;
            })
            ->andReturn(collect([$event1, $event2]));

        $recurringEvent1 = RecurringEventFactory::make();
        $recurringEvent2 = RecurringEventFactory::make();
        $event1->shouldReceive('getRecurringEventOnDate')
            ->once()
            ->with($date)
            ->andReturn($recurringEvent1);
        $event2->shouldReceive('getRecurringEventOnDate')
            ->once()
            ->with($date)
            ->andReturn($recurringEvent2);

        $result = $this->query->get($officeId, $date);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertContains($recurringEvent1, $result);
        $this->assertContains($recurringEvent2, $result);
    }
}
