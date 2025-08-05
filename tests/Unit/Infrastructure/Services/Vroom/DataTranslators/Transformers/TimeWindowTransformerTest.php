<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\TimeWindowTransformer;
use App\Infrastructure\Services\Vroom\DTO\VroomTimeWindow;
use Carbon\Carbon;
use Tests\TestCase;

class TimeWindowTransformerTest extends TestCase
{
    private const START_TIMESTAMP = 1687935600;
    private const END_TIMESTAMP = 1687935600;

    /**
     * @test
     */
    public function transform_time_window(): void
    {
        $timeWindow = $this->getTimeWindow();
        $actual = (new TimeWindowTransformer())->transform($timeWindow);

        $this->assertEquals($this->expected(), $actual);
    }

    private function expected(): VroomTimeWindow
    {
        return new VroomTimeWindow(
            Carbon::createFromTimestamp(self::START_TIMESTAMP),
            Carbon::createFromTimestamp(self::END_TIMESTAMP)
        );
    }

    private function getTimeWindow(): TimeWindow
    {
        return new TimeWindow(
            Carbon::createFromTimestamp(self::START_TIMESTAMP),
            Carbon::createFromTimestamp(self::END_TIMESTAMP),
        );
    }
}
