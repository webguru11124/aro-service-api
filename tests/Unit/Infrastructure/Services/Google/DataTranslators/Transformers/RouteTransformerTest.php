<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Infrastructure\Services\Google\DataTranslators\Transformers\RouteTransformer;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\TestValue;

class RouteTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_transforms_route_object(): void
    {
        $overrides = [
            'id' => TestValue::ROUTE_ID,
        ];
        $route = RouteFactory::make($overrides);

        $vehicle = (new RouteTransformer())->transform($route);

        $this->assertEquals(TestValue::ROUTE_ID, $vehicle->getLabel());
        $this->assertEquals(TestValue::MIN_LATITUDE, $vehicle->getStartLocation()->getLatitude());
        $this->assertEquals(TestValue::MIN_LONGITUDE, $vehicle->getStartLocation()->getLongitude());
        $this->assertEquals(TestValue::MAX_LATITUDE, $vehicle->getEndLocation()->getLatitude());
        $this->assertEquals(TestValue::MAX_LONGITUDE, $vehicle->getEndLocation()->getLongitude());
    }

    /**
     * @test
     */
    public function it_transforms_route_object_without_breaks(): void
    {
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'workEvents' => [AppointmentFactory::make()],
        ]);

        $vehicle = (new RouteTransformer())->transform($route);

        $this->assertNull($vehicle->getBreakRule());
    }
}
