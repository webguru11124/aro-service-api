<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\Google\DataTranslators\Transformers\TimeWindowTransformer;
use Carbon\Carbon;
use Tests\TestCase;

class TimeWindowTransformerTest extends TestCase
{
    private const START_TIME = '2023-12-01T08:00:00';
    private const END_TIME = '2023-12-01T17:00:00';

    /**
     * @test
     */
    public function it_transforms_time_window_object(): void
    {
        $startAt = new Carbon(self::START_TIME);
        $endAt = new Carbon(self::END_TIME);
        $domainTimeWindow = new TimeWindow($startAt, $endAt);

        $googleTimeWindow = (new TimeWindowTransformer())->transform($domainTimeWindow);

        $expected = json_encode([
            'startTime' => self::START_TIME . 'Z',
            'endTime' => self::END_TIME . 'Z',
        ]);

        $this->assertEquals($expected, $googleTimeWindow->serializeToJsonString());
    }
}
