<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Transformer;

use App\Application\DTO\OverrideDTO;
use App\Application\Http\Api\Calendar\V1\Requests\EventOverrideRequest;
use App\Application\Transformer\OverrideDtoTransformer;
use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonTimeZone;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

class OverrideDtoTransformerTest extends TestCase
{
    private MockInterface|CalendarEventRepository $mockEventRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockEventRepository = Mockery::mock(CalendarEventRepository::class);
    }

    /**
     * @test
     */
    public function it_transforms_from_request_correctly(): void
    {
        $mockRequest = Mockery::mock(EventOverrideRequest::class);
        $mockRequest->shouldReceive('get')->with('date')->andReturn('2021-01-01');
        $mockRequest->shouldReceive('get')->with('is_canceled', false)->andReturn(false);
        $mockRequest->shouldReceive('get')->with('title')->andReturn('test_title');
        $mockRequest->shouldReceive('get')->with('description')->andReturn('test_description');
        $mockRequest->shouldReceive('has')->with('description')->andReturnTrue();
        $mockRequest->shouldReceive('get')->with('start_time')->andReturn('10:00:00');
        $mockRequest->shouldReceive('get')->with('end_time')->andReturn('11:30:00');
        $mockRequest->shouldReceive('get')->with('location_lat')->andReturn(46.6343);
        $mockRequest->shouldReceive('get')->with('location_lng')->andReturn(-122.123);
        $mockRequest->shouldReceive('get')->with('meeting_link')->andReturn('test_link');
        $mockRequest->shouldReceive('get')->with('address')->andReturn('test_address');
        $mockRequest->shouldReceive('get')->with('city')->andReturn('test_city');
        $mockRequest->shouldReceive('get')->with('state')->andReturn('test_state');
        $mockRequest->shouldReceive('get')->with('zip')->andReturn('test_zip');

        $eventDetails = new EventDetails(
            'test_title',
            'test_description',
            '10:00:00',
            '11:30:00',
            new CarbonTimeZone('America/Los_Angeles'),
            new Coordinate(46.6343, -122.123),
        );

        $mockSchedule = Mockery::mock(Event::class);
        $mockSchedule->shouldReceive('getRecurringEventOnDate')->andReturnNull();
        $mockSchedule->shouldReceive('getId')->andReturn(TestValue::EVENT_ID);
        $mockSchedule->shouldReceive('getEventDetails')->andReturn($eventDetails);
        $mockSchedule->shouldReceive('isCanceledOnDate')->andReturn(false);

        $this->mockEventRepository->shouldReceive('getEventOverridesNextId')->andReturn(1);

        $transformer = new OverrideDtoTransformer($this->mockEventRepository);
        $dto = $transformer->transformFromRequest($mockRequest, $mockSchedule);

        $this->assertInstanceOf(OverrideDTO::class, $dto);
        $this->assertEquals(1, $dto->id);
        $this->assertEquals(TestValue::EVENT_ID, $dto->eventId);
        $this->assertFalse($dto->isCanceled);
        $this->assertEquals('2021-01-01', $dto->date->toDateString());
        $this->assertEquals('test_title', $dto->title);
        $this->assertEquals('test_description', $dto->description);
        $this->assertEquals('10:00:00', $dto->startTime);
        $this->assertEquals('11:30:00', $dto->endTime);
        $this->assertEquals('America/Los_Angeles', $dto->timeZone);
        $this->assertEquals(46.6343, $dto->location->getLatitude());
        $this->assertEquals(-122.123, $dto->location->getLongitude());
        $this->assertEquals('test_link', $dto->meetingLink);
        $this->assertEquals('test_address', $dto->address->getAddress());
        $this->assertEquals('test_city', $dto->address->getCity());
        $this->assertEquals('test_state', $dto->address->getState());
        $this->assertEquals('test_zip', $dto->address->getZip());
    }

    /**
     * @test
     */
    public function it_transforms_data_without_address(): void
    {
        $mockRequest = Mockery::mock(EventOverrideRequest::class);
        $mockRequest->shouldReceive('get')->with('date')->andReturn('2021-01-01');
        $mockRequest->shouldReceive('get')->with('is_canceled', false)->andReturn(false);
        $mockRequest->shouldReceive('get')->with('title')->andReturn('test_title');
        $mockRequest->shouldReceive('get')->with('description')->andReturnNull();
        $mockRequest->shouldReceive('has')->with('description')->andReturnTrue();
        $mockRequest->shouldReceive('get')->with('start_time')->andReturn('10:00:00');
        $mockRequest->shouldReceive('get')->with('end_time')->andReturn('11:30:00');
        $mockRequest->shouldReceive('get')->with('location_lat')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('location_lng')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('meeting_link')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('address')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('city')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('state')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('zip')->andReturnNull();

        $eventDetails = new EventDetails(
            'test_title',
            'test_description',
            '10:00:00',
            '11:30:00',
            new CarbonTimeZone('America/Los_Angeles'),
            null,
        );

        $mockSchedule = Mockery::mock(Event::class);
        $mockSchedule->shouldReceive('getRecurringEventOnDate')->andReturnNull();
        $mockSchedule->shouldReceive('getId')->andReturn(TestValue::EVENT_ID);
        $mockSchedule->shouldReceive('getEventDetails')->andReturn($eventDetails);
        $mockSchedule->shouldReceive('isCanceledOnDate')->andReturn(false);

        $this->mockEventRepository->shouldReceive('getEventOverridesNextId')->andReturn(1);

        $transformer = new OverrideDtoTransformer($this->mockEventRepository);
        $dto = $transformer->transformFromRequest($mockRequest, $mockSchedule);

        $this->assertInstanceOf(OverrideDTO::class, $dto);
        $this->assertEquals(1, $dto->id);
        $this->assertEquals(TestValue::EVENT_ID, $dto->eventId);
        $this->assertFalse($dto->isCanceled);
        $this->assertEquals('2021-01-01', $dto->date->toDateString());
        $this->assertEquals('test_title', $dto->title);
        $this->assertEquals('', $dto->description);
        $this->assertEquals('10:00:00', $dto->startTime);
        $this->assertEquals('11:30:00', $dto->endTime);
        $this->assertEquals('America/Los_Angeles', $dto->timeZone);
        $this->assertNull($dto->location);
        $this->assertNull($dto->meetingLink);
        $this->assertNull($dto->address);
    }

    /**
     * @test
     *
     * @dataProvider provideDescriptionData
     */
    public function it_provides_description_correctly(
        bool $hasDescription,
        string|null $description,
        string $expectedDescription
    ): void {
        $mockRequest = Mockery::mock(EventOverrideRequest::class);
        $mockRequest->shouldReceive('get')->with('date')->andReturn('2021-01-01');
        $mockRequest->shouldReceive('get')->with('is_canceled', false)->andReturn(false);
        $mockRequest->shouldReceive('get')->with('title')->andReturn('test_title');
        if ($hasDescription) {
            $mockRequest->shouldReceive('get')->with('description')->andReturn($description);
        }
        $mockRequest->shouldReceive('has')->with('description')->andReturn($hasDescription);
        $mockRequest->shouldReceive('get')->with('start_time')->andReturn('10:00:00');
        $mockRequest->shouldReceive('get')->with('end_time')->andReturn('11:30:00');
        $mockRequest->shouldReceive('get')->with('location_lat')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('location_lng')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('meeting_link')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('address')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('city')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('state')->andReturnNull();
        $mockRequest->shouldReceive('get')->with('zip')->andReturnNull();

        $eventDetails = new EventDetails(
            'test_title',
            TestValue::EVENT_DESCRIPTION,
            '10:00:00',
            '11:30:00',
            new CarbonTimeZone('America/Los_Angeles'),
            null,
        );

        $mockSchedule = Mockery::mock(Event::class);
        $mockSchedule->shouldReceive('getRecurringEventOnDate')->andReturnNull();
        $mockSchedule->shouldReceive('getId')->andReturn(TestValue::EVENT_ID);
        $mockSchedule->shouldReceive('getEventDetails')->andReturn($eventDetails);
        $mockSchedule->shouldReceive('isCanceledOnDate')->andReturn(false);

        $this->mockEventRepository->shouldReceive('getEventOverridesNextId')->andReturn(1);

        $transformer = new OverrideDtoTransformer($this->mockEventRepository);
        $dto = $transformer->transformFromRequest($mockRequest, $mockSchedule);

        $this->assertInstanceOf(OverrideDTO::class, $dto);
        $this->assertEquals($expectedDescription, $dto->description);
    }

    public static function provideDescriptionData(): array
    {
        return [
            'description is not set' => [false, null, TestValue::EVENT_DESCRIPTION],
            'description is set with empty string' => [true, '', ''],
            'description is set with valid string' => [true, 'new_description', 'new_description'],
        ];
    }
}
