<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Resources;

use App\Application\Http\Api\Calendar\V1\Resources\CalendarEventTypeResource;
use App\Domain\Calendar\Enums\EventType;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CalendarEventTypeResourceTest extends TestCase
{
    private CalendarEventTypeResource $resource;
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
        $eventType = EventType::OFFICE_EVENT;
        $this->resource = new CalendarEventTypeResource($eventType);

        $resource = $this->resource->toArray($this->request);

        $this->assertEquals(
            [
                'id' => 'office-event',
                'name' => 'Office Event',
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
