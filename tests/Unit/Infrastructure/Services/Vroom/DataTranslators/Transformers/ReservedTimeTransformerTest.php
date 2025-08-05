<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use Tests\TestCase;
use Tests\Tools\Factories\ReservedTimeFactory;
use App\Infrastructure\Services\Vroom\DTO\VroomBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\ReservedTimeTransformer;

class ReservedTimeTransformerTest extends TestCase
{
    private ReservedTimeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new ReservedTimeTransformer();
    }

    /**
     * @test
     */
    public function it_transforms_reserved_time_to_vroom_break(): void
    {
        /** @var ReservedTime $reservedTime */
        $reservedTime = ReservedTimeFactory::make();
        $actual = $this->transformer->transform($reservedTime);

        $this->assertInstanceOf(VroomBreak::class, $actual);
        $this->assertEquals([
            'id' => $reservedTime->getId(),
            'description' => $reservedTime->getDescription(),
            'service' => $reservedTime->getDuration()->getTotalSeconds(),
            'time_windows' => [
                [
                    $reservedTime->getTimeWindow()->getStartAt()->timestamp,
                    $reservedTime->getTimeWindow()->getEndAt()->timestamp,
                ],
            ],
        ], $actual->toArray());
    }
}
