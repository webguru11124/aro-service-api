<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Infrastructure\Services\Google\DataTranslators\Transformers\DurationTransformer;
use Carbon\CarbonInterval;
use Tests\TestCase;

class DurationTransformerTest extends TestCase
{
    private const DURATION_IN_SECONDS = 3600;

    /**
     * @test
     */
    public function it_transforms_duration_object(): void
    {
        $domainDuration = new Duration(CarbonInterval::seconds(self::DURATION_IN_SECONDS));

        $googleDuration = (new DurationTransformer())->transform($domainDuration);

        $this->assertEquals(self::DURATION_IN_SECONDS, (int) $googleDuration->getSeconds());
    }
}
