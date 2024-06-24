<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Infrastructure\Services\Google\DataTranslators\Transformers\DateTimeTransformer;
use DateTime;
use Tests\TestCase;

class DateTimeTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_transforms_date_time_object(): void
    {
        $dateTime = new DateTime('2024-01-01T00:00:00');

        $timestamp = (new DateTimeTransformer())->transform($dateTime);

        $this->assertEquals(1704067200, $timestamp->getSeconds());
    }
}
