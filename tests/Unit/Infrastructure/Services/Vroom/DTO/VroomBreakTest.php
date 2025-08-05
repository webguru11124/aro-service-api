<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DTO;

use App\Infrastructure\Services\Vroom\DTO\VroomBreak;
use App\Infrastructure\Services\Vroom\DTO\VroomTimeWindow;
use Carbon\Carbon;
use Tests\TestCase;

class VroomBreakTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider toArrayDataProvider
     */
    public function it_transforms_to_array_properly(VroomBreak $vroomBreak, array $expected): void
    {
        $this->assertEquals($expected, $vroomBreak->toArray());
    }

    public static function toArrayDataProvider(): iterable
    {
        $timeWindowStart = Carbon::tomorrow()->hour(9);
        $timeWindowEnd = Carbon::tomorrow()->hour(10);
        $arguments = [
            'id' => random_int(1, 3),
            'description' => 'Test description',
            'service' => 900,
            'maxLoad' => null,
            'timeWindow' => new VroomTimeWindow($timeWindowStart, $timeWindowEnd),
        ];

        yield 'without_max_load' => [
            new VroomBreak(...$arguments),
            [
                'id' => $arguments['id'],
                'description' => $arguments['description'],
                'service' => $arguments['service'],
                'time_windows' => [
                    'timeWindow' => [$timeWindowStart->timestamp, $timeWindowEnd->timestamp],
                ],
            ],
        ];

        yield 'with_max_load' => [
            new VroomBreak(...array_merge($arguments, ['maxLoad' => $maxLoad = random_int(10, 20)])),
            [
                'id' => $arguments['id'],
                'description' => $arguments['description'],
                'service' => $arguments['service'],
                'time_windows' => [
                    'timeWindow' => [$timeWindowStart->timestamp, $timeWindowEnd->timestamp],
                ],
                'max_load' => [$maxLoad],
            ],
        ];
    }
}
