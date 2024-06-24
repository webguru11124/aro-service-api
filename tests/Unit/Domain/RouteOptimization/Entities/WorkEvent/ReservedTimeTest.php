<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Tests\TestCase;
use Tests\Tools\Factories\ReservedTimeFactory;

class ReservedTimeTest extends TestCase
{
    use CloneWorkEvent;

    private const RESERVED_TIME_TYPE = 'ReservedTime';

    /**
     * @test
     */
    public function duration_can_be_set(): void
    {
        $reservedTime = $this->getReservedTime();

        $durationMin = random_int(16, 20);
        $newDuration = Duration::fromMinutes($durationMin);
        $reservedTime->setDuration($newDuration);

        $this->assertEquals($durationMin, $reservedTime->getDuration()->getTotalMinutes());
    }

    /**
     * @test
     */
    public function it_gets_the_type(): void
    {
        $reservedTime = $this->getReservedTime();

        $this->assertEquals(self::RESERVED_TIME_TYPE, $reservedTime->getType()->value);
    }

    private function getReservedTime(): ReservedTime
    {
        return new ReservedTime(
            $this->faker->randomNumber(6),
            'Not Working'
        );
    }

    private function getSubject(): WorkEvent
    {
        return ReservedTimeFactory::make();
    }
}
