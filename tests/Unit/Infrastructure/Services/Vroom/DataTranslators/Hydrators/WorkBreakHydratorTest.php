<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators\Hydrators;

use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\Vroom\DataTranslators\Hydrators\WorkBreakHydrator;
use App\Infrastructure\Services\Vroom\Enums\StepType;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Tests\TestCase;
use Tests\Traits\VroomDataAndObjects;

class WorkBreakHydratorTest extends TestCase
{
    use VroomDataAndObjects;

    private const TIME_ZONE = 'PST';

    private CarbonTimeZone $timeZone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = new WorkBreakHydrator();
        $this->timeZone = CarbonTimeZone::create(self::TIME_ZONE);
    }

    /**
     * @test
     */
    public function hydrate_lunch(): void
    {
        $actual = $this->translator->hydrate($this->lunch(), $this->timeZone);
        $this->assertEquals($this->getExpectedLunch(), $actual);
    }

    /**
     * @test
     */
    public function hydrate_work_break(): void
    {
        $actual = $this->translator->hydrate($this->workBreak(), $this->timeZone);
        $this->assertEquals($this->getExpectedWorkBreak(), $actual);
    }

    private function getExpectedLunch(): Lunch
    {
        return (new Lunch(
            self::WORK_BREAK_LUNCH_ID,
            self::WORK_BREAK_LUNCH_LABEL,
        ))
        ->setTimeWindow(
            new TimeWindow(
                Carbon::createFromTimestamp(1687007100),
                Carbon::createFromTimestamp(1687008900),
            )
        )
        ->setDuration($this->domainDuration30Minutes());
    }

    private function getExpectedWorkBreak(): WorkBreak
    {
        return (new WorkBreak(
            self::WORK_BREAK_15_MINUTE_ID,
            self::WORK_BREAK_15_MINUTE_LABEL,
        ))
        ->setTimeWindow(
            new TimeWindow(
                Carbon::createFromTimestamp(1687007100),
                Carbon::createFromTimestamp(1687008000),
            )
        )
        ->setDuration($this->domainDuration15Minutes());
    }

    private function lunch(): array
    {
        return [
            'arrival' => 1687007100,
            'description' => self::WORK_BREAK_LUNCH_LABEL,
            'duration' => 2063,
            'id' => self::WORK_BREAK_LUNCH_ID,
            'load' => [
                5,
            ],
            'location_index' => 0,
            'setup' => 0,
            'service' => self::TIMESTAMP_30_MINUTES,
            'type' => StepType::BREAK->value,
            'violations' => [
            ],
            'waiting_time' => 0,
        ];
    }

    private function workBreak(): array
    {
        return [
            'arrival' => 1687007100,
            'description' => self::WORK_BREAK_15_MINUTE_LABEL,
            'duration' => 2063,
            'id' => self::WORK_BREAK_15_MINUTE_ID,
            'load' => [
                5,
            ],
            'location_index' => 0,
            'setup' => 0,
            'service' => self::TIMESTAMP_15_MINUTES,
            'type' => StepType::BREAK->value,
            'violations' => [
            ],
            'waiting_time' => 0,
        ];
    }
}
