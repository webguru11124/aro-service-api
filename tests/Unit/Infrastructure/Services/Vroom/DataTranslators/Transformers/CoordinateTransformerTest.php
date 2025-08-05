<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\CoordinateTransformer;
use Tests\TestCase;
use Tests\Tools\TestValue;

class CoordinateTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function transform_coordinate(): void
    {
        $coordinate = $this->getCoordinate();
        $actual = (new CoordinateTransformer())->transform($coordinate);

        $this->assertEquals($this->expected(), $actual->toArray());
    }

    private function expected(): array
    {
        return [
            TestValue::LONGITUDE,
            TestValue::LATITUDE,
        ];
    }

    private function getCoordinate(): Coordinate
    {
        return new Coordinate(
            TestValue::LATITUDE,
            TestValue::LONGITUDE,
        );
    }
}
