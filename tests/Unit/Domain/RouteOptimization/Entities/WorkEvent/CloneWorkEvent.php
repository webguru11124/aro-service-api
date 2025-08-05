<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;

trait CloneWorkEvent
{
    /**
     * @test
     */
    public function it_is_cloned_with_new_properties(): void
    {
        $subject = $this->getSubject();

        $originalTimeWindow = $subject->getTimeWindow();
        $originalDuration = $subject->getDuration();

        $cloned = clone $subject;

        $clonedTimeWindow = $cloned->getTimeWindow();
        $clonedDuration = $cloned->getDuration();

        $this->assertNotEquals(spl_object_id($clonedTimeWindow), spl_object_id($originalTimeWindow));
        $this->assertNotEquals(spl_object_id($clonedDuration), spl_object_id($originalDuration));
    }

    abstract public static function assertNotEquals(mixed $expected, mixed $actual, string $message = ''): void;

    abstract private function getSubject(): WorkEvent;
}
