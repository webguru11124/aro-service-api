<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Google\DataTranslators\Transformers\CoordinateTransformer;
use Tests\TestCase;
use Tests\Tools\TestValue;

class CoordinateTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_transforms_coordinate_object(): void
    {
        $coordinate = new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE);

        $latLng = (new CoordinateTransformer())->transform($coordinate);

        $this->assertEquals(TestValue::LATITUDE, $latLng->getLatitude());
        $this->assertEquals(TestValue::LONGITUDE, $latLng->getLongitude());
    }
}
