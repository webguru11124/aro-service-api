<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Resources;

use App\Application\Http\Api\Calendar\V1\Resources\CalendarEventResource;
use App\Domain\Calendar\Entities\RecurringEvent;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\RecurringEventFactory;

class CalendarEventResourceTest extends TestCase
{
    private CalendarEventResource $resource;
    private Request|MockInterface $request;

    protected function setup(): void
    {
        parent::setUp();

        $this->request = Mockery::mock(Request::class);
    }

    /**
     * @test
     */
    public function it_creates_expected_array_representation_of_resource(): void
    {
        /** @var RecurringEvent $event */
        $event = RecurringEventFactory::make();
        $this->resource = new CalendarEventResource($event);

        $resource = $this->resource->toArray($this->request);

        $this->assertEquals(
            [
                'id' => $event->getIdentity()->getId(),
                'office_id' => $event->getIdentity()->getOfficeId(),
                'start_date' => $event->getStartDate()->toDateString(),
                'end_date' => $event->getEndDate()->toDateString(),
                'repeat_every' => $event->getRepeatEvery(),
                'interval' => $event->getInterval()->value,
                'week_days' => $event->getWeekDaysAsCsv(),
                'week_num' => $event->getWeeklyOccurrence()->weekNumInMonth->value,
                'service_pro_ids' => $event->getParticipantIds()->values(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'event_type' => $event->getEventType()->value,
                'start_at' => $event->getStartTime(),
                'end_at' => $event->getEndTime(),
                'time_zone' => $event->getTimezoneFullName(),
                'location' => [
                    'lat' => $event->getLocation()->getLatitude(),
                    'lng' => $event->getLocation()->getLongitude(),
                ],
                'meeting_link' => $event->getMeetingLink(),
                'address' => [
                    'address' => $event->getAddress()->getAddress(),
                    'city' => $event->getAddress()->getCity(),
                    'state' => $event->getAddress()->getState(),
                    'zip' => $event->getAddress()->getZip(),
                ],
                'override_id' => null,
                'is_canceled' => 'false',
            ],
            $resource
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->request);
        unset($this->resource);
    }
}
