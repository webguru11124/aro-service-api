<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\TravelFactory;
use Tests\Tools\TestValue;

class TravelTest extends TestCase
{
    private const THIRTY_MINUTES = 30 * 60;
    private const TRAVEL_ID = 421;

    /**
     * @test
     */
    public function it_can_be_created_and_returns_proper_values(): void
    {
        Carbon::setTestNow('now');
        $timeWindow = new TimeWindow(Carbon::now(), Carbon::now()->addMinutes(30));
        $distance = Distance::fromMeters($this->faker->randomNumber(4));

        $travel = new Travel(
            $distance,
            $timeWindow,
            self::TRAVEL_ID,
        );
        $travel->setRouteId(TestValue::ROUTE_ID);

        $this->assertSame(self::TRAVEL_ID, $travel->getId());
        $this->assertEquals(Duration::fromSeconds(self::THIRTY_MINUTES)->getTotalSeconds(), $travel->getDuration()->getTotalSeconds());
        $this->assertSame($distance, $travel->getDistance());
        $this->assertSame($timeWindow, $travel->getTimeWindow());
        $this->assertSame(TestValue::ROUTE_ID, $travel->getRouteId());
    }

    /**
     * @test
     */
    public function it_is_cloned_with_new_properties(): void
    {
        /** @var Travel $subject */
        $subject = TravelFactory::make();

        $originalTimeWindow = $subject->getTimeWindow();
        $originalDuration = $subject->getDuration();
        $originalDistance = $subject->getDistance();

        $cloned = clone $subject;

        $clonedTimeWindow = $cloned->getTimeWindow();
        $clonedDuration = $cloned->getDuration();
        $clonedDistance = $cloned->getDistance();

        $this->assertNotEquals(spl_object_id($clonedTimeWindow), spl_object_id($originalTimeWindow));
        $this->assertNotEquals(spl_object_id($clonedDuration), spl_object_id($originalDuration));
        $this->assertNotEquals(spl_object_id($clonedDistance), spl_object_id($originalDistance));
    }
}
