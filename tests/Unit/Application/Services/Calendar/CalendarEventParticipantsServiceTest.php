<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Services\Calendar;

use App\Application\Services\Calendar\CalendarEventParticipantsService;
use App\Domain\Calendar\Entities\Event;
use App\Domain\Contracts\Queries\EventParticipantQuery;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CalendarEventParticipantsServiceTest extends TestCase
{
    private EventParticipantQuery|MockInterface $mockParticipantQuery;
    private CalendarEventParticipantsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockParticipantQuery = Mockery::mock(EventParticipantQuery::class);

        $this->service = new CalendarEventParticipantsService(
            $this->mockParticipantQuery,
        );
    }

    /**
     * @test
     */
    public function it_should_return_participants_for_event(): void
    {
        $this->mockParticipantQuery->shouldReceive('find')
            ->once()
            ->with(Mockery::type(Event::class))
            ->andReturn(Mockery::mock(Collection::class));

        $this->service->getParticipants(Mockery::mock(Event::class));
    }
}
