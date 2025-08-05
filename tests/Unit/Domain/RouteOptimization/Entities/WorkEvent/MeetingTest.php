<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\TestValue;

class MeetingTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_meeting(): void
    {
        $startAt = Carbon::today()->setTimeFromTimeString('08:10');
        $endAt = Carbon::today()->setTimeFromTimeString('09:10');

        $meeting = new Meeting(
            TestValue::EVENT_ID,
            'Meeting',
            new TimeWindow($startAt, $endAt),
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE)
        );

        $this->assertEquals(TestValue::EVENT_ID, $meeting->getId());
        $this->assertEquals('Meeting', $meeting->getDescription());
        $this->assertEquals(WorkEventType::MEETING, $meeting->getType());
        $this->assertEquals(60, $meeting->getDuration()->getTotalMinutes());
        $this->assertEquals('#EVENT Meeting [08:10 - 09:10], [40.3028, -111.662]', $meeting->getFormattedDescription());
        $this->assertEquals($startAt, $meeting->getExpectedArrival()->getStartAt());
        $this->assertEquals($endAt, $meeting->getExpectedArrival()->getEndAt());
        $this->assertEquals($startAt, $meeting->getTimeWindow()->getStartAt());
        $this->assertEquals($endAt, $meeting->getTimeWindow()->getEndAt());
    }
}
