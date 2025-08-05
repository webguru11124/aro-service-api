<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\Weather;

use Tests\TestCase;
use App\Domain\RouteOptimization\Entities\Weather\WeatherCondition;

class WeatherConditionTest extends TestCase
{
    /**
     * @test
     */
    public function test_it_can_be_created()
    {
        $weatherCondition = new WeatherCondition(
            id: 200,
            main: 'Thunderstorm',
            description: 'thunderstorm with light rain',
            iconCode: '11d',
        );

        $this->assertEquals(200, $weatherCondition->getId());
        $this->assertEquals('Thunderstorm', $weatherCondition->getMain());
        $this->assertEquals('thunderstorm with light rain', $weatherCondition->getDescription());
        $this->assertEquals('11d', $weatherCondition->getIconCode());
        $this->assertTrue($weatherCondition->isInclement());
    }

    /**
     * @test
     */
    public function test_it_can_be_created_with_not_inclement_weather(): void
    {
        $weatherCondition = new WeatherCondition(
            id: 800,
            main: 'Clear',
            description: 'clear sky',
            iconCode: '01d',
        );

        $this->assertEquals(800, $weatherCondition->getId());
        $this->assertEquals('Clear', $weatherCondition->getMain());
        $this->assertEquals('clear sky', $weatherCondition->getDescription());
        $this->assertEquals('01d', $weatherCondition->getIconCode());
        $this->assertFalse($weatherCondition->isInclement());
    }
}
