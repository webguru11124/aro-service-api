<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\Factories;

use App\Application\DTO\OverrideDTO;
use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\Factories\EventOverrideFactory;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use stdClass;
use Tests\TestCase;

class EventOverrideFactoryTest extends TestCase
{
    private EventOverrideFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new EventOverrideFactory();
    }

    /**
     * @test
     */
    public function it_creates_schedule_override_from_raw_data(): void
    {
        $rawData = new stdClass();
        $rawData->id = 1;
        $rawData->schedule_id = 1001;
        $rawData->is_canceled = false;
        $rawData->date = '2023-07-24';
        $rawData->time_zone = 'UTC';
        $rawData->title = 'Sample Title';
        $rawData->description = 'Sample Description';
        $rawData->start_time = '08:00:00';
        $rawData->end_time = '09:00:00';
        $rawData->location = json_encode(['lat' => 40.7128, 'lon' => -74.0060]);
        $rawData->meeting_link = 'https://meet.google.com/xxx-xxx-xxx';
        $rawData->address = '{"address": "66 ave, 666", "city": "New York","state": "NY","zip": "66666"}';

        $override = $this->factory->createFromRawData($rawData);

        $this->assertInstanceOf(Override::class, $override);
        $this->assertEquals(1, $override->getId());
        $this->assertEquals(1001, $override->getEventId());
        $this->assertFalse($override->isCanceled());
        $this->assertEquals(Carbon::parse('2023-07-24', 'UTC'), $override->getDate());
        $this->assertEquals($rawData->meeting_link, $override->getEventDetails()->getMeetingLink());
        $this->assertEquals(
            '66 ave, 666 New York NY 66666',
            $override->getEventDetails()->getAddress()->getFullAddress()
        );
    }

    /**
     * @test
     */
    public function it_creates_schedule_override_from_dto(): void
    {
        $overrideDto = new OverrideDTO(
            id: 1234,
            eventId: 1,
            isCanceled: false,
            date: Carbon::parse('2023-07-24', 'UTC'),
            title: 'Sample Title',
            description: 'Sample Description',
            startTime: '08:00:00',
            endTime: '09:00:00',
            timeZone: CarbonTimeZone::create('UTC'),
            location: new Coordinate(40.7128, -74.0060),
            meetingLink: 'meeting link',
            address: new Address(
                address: 'address',
                city: 'city',
                state: 'state',
                zip: 'zip'
            )
        );

        $override = $this->factory->createFromDTO($overrideDto);

        $this->assertInstanceOf(Override::class, $override);
        $this->assertEquals(1234, $override->getId());
        $this->assertEquals(1, $override->getEventId());
        $this->assertFalse($override->isCanceled());
        $this->assertEquals(Carbon::parse('2023-07-24', 'UTC'), $override->getDate());
        $this->assertEquals('Sample Title', $override->getEventDetails()->getTitle());
        $this->assertEquals('Sample Description', $override->getEventDetails()->getDescription());
        $this->assertEquals('08:00:00', $override->getEventDetails()->getStartTime());
        $this->assertEquals('09:00:00', $override->getEventDetails()->getEndTime());
        $this->assertEquals('UTC', $override->getEventDetails()->getTimeZone()->getName());
        $this->assertEquals(40.7128, $override->getEventDetails()->getLocation()->getLatitude());
        $this->assertEquals(-74.0060, $override->getEventDetails()->getLocation()->getLongitude());
        $this->assertEquals($overrideDto->meetingLink, $override->getEventDetails()->getMeetingLink());
        $this->assertSame($overrideDto->address, $override->getEventDetails()->getAddress());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->factory);
    }
}
