<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use PHPUnit\Framework\TestCase;
use Tests\Tools\Factories\WeatherInfoFactory;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Domain\RouteOptimization\ValueObjects\ServiceDuration;
use App\Domain\RouteOptimization\Entities\Weather\WeatherCondition;

class ServiceDurationTest extends TestCase
{
    private const LINEAR_FOOT_PER_SECOND = 1.45;

    private $propertyDetailsMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyDetailsMock = $this->createMock(PropertyDetails::class);
    }

    /**
     * @test
     */
    public function it_calculates_minimum_duration_correctly(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $weatherInfo = WeatherInfoFactory::make(
            [
                'weatherCondition' => new WeatherCondition(
                    id: 100,
                    main: 'Clear',
                    description: 'clear sky',
                    iconCode: '11d',
                ),
            ]
        );

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null, $weatherInfo);

        $expectedDuration = Duration::fromSeconds((int) (360 + self::LINEAR_FOOT_PER_SECOND * (4 * sqrt(10000.0) + 4 * sqrt(5000.0))));

        $this->assertEquals($expectedDuration, $serviceDuration->getMinimumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_minimum_duration_with_inclement_weather(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $weatherInfo = WeatherInfoFactory::make(
            [
                'weatherCondition' => new WeatherCondition(
                    id: 200,
                    main: 'Thunderstorm',
                    description: 'thunderstorm with light rain',
                    iconCode: '11d',
                ),
            ]
        );

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null, $weatherInfo);

        $expectedDuration = Duration::fromSeconds((int) (360 + self::LINEAR_FOOT_PER_SECOND / 2 * (4 * sqrt(10000.0) + 4 * sqrt(5000.0))));

        $this->assertEquals($expectedDuration, $serviceDuration->getMinimumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_minimum_duration_with_weather_unavailable(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null);

        $expectedDuration = Duration::fromSeconds((int) (360 + self::LINEAR_FOOT_PER_SECOND * (4 * sqrt(10000.0) + 4 * sqrt(5000.0))));

        $this->assertEquals($expectedDuration, $serviceDuration->getMinimumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_maximum_duration_with_inclement_weather(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $weatherInfo = WeatherInfoFactory::make(
            [
                'weatherCondition' => new WeatherCondition(
                    id: 200,
                    main: 'Thunderstorm',
                    description: 'thunderstorm with light rain',
                    iconCode: '11d',
                ),
            ]
        );

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null, $weatherInfo);

        $expectedDuration = Duration::fromSeconds((int) (360 + pow(self::LINEAR_FOOT_PER_SECOND / 2, 2.5) * (4 * sqrt(10000.0) + 4 * sqrt(5000.0))));

        $this->assertEquals($expectedDuration, $serviceDuration->getMaximumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_maximum_duration_correctly(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $weatherInfo = WeatherInfoFactory::make(
            [
                'weatherCondition' => new WeatherCondition(
                    id: 100,
                    main: 'Clear',
                    description: 'clear sky',
                    iconCode: '11d',
                ),
            ]
        );

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null, $weatherInfo);

        $expectedDuration = Duration::fromSeconds((int) (360 + pow(self::LINEAR_FOOT_PER_SECOND, 2.5) * (4 * sqrt(10000.0) + 4 * sqrt(5000.0))));

        $this->assertEquals($expectedDuration, $serviceDuration->getMaximumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_maximum_duration_with_weather_unavailable(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock);

        $expectedDuration = Duration::fromSeconds((int) (360 + pow(self::LINEAR_FOOT_PER_SECOND, 2.5) * (4 * sqrt(10000.0) + 4 * sqrt(5000.0))));

        $this->assertEquals($expectedDuration, $serviceDuration->getMaximumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_optimum_duration_correctly(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $weatherInfo = WeatherInfoFactory::make(
            [
                'weatherCondition' => new WeatherCondition(
                    id: 100,
                    main: 'Clear',
                    description: 'clear sky',
                    iconCode: '11d',
                ),
            ]
        );

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null, $weatherInfo);

        $minPart = self::LINEAR_FOOT_PER_SECOND * (4 * sqrt(10000.0) + 4 * sqrt(5000.0));
        $maxPart = pow(self::LINEAR_FOOT_PER_SECOND, 2.5) * (4 * sqrt(10000.0) + 4 * sqrt(5000.0));
        $expectedDuration = Duration::fromSeconds((int) (360 + ($minPart + $maxPart) / 2));

        $this->assertEquals($expectedDuration, $serviceDuration->getOptimumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_optimum_duration_with_weather_unavailable(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock);

        $minPart = self::LINEAR_FOOT_PER_SECOND * (4 * sqrt(10000.0) + 4 * sqrt(5000.0));
        $maxPart = pow(self::LINEAR_FOOT_PER_SECOND, 2.5) * (4 * sqrt(10000.0) + 4 * sqrt(5000.0));
        $expectedDuration = Duration::fromSeconds((int) (360 + ($minPart + $maxPart) / 2));

        $this->assertEquals($expectedDuration, $serviceDuration->getOptimumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_minimum_duration_with_historical_average_duration_correctly(): void
    {
        $landSqFt = 10000.0;
        $buildingSqFt = 5000.0;
        $historicalAverageDuration = 20;

        $this->propertyDetailsMock->method('getLandSqFt')->willReturn($landSqFt);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn($buildingSqFt);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, $historicalAverageDuration);

        $perimeter = 4 * (sqrt($landSqFt) + sqrt($buildingSqFt));
        $linearFootPerSecond = (60 * $historicalAverageDuration - 360) / $perimeter;
        $minimumDurationSeconds = 360 + $linearFootPerSecond * $perimeter;

        $expectedDuration = Duration::fromSeconds((int) $minimumDurationSeconds);

        $this->assertEquals($expectedDuration, $serviceDuration->getMinimumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_optimum_duration_with_inclement_weather(): void
    {
        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $weatherInfo = WeatherInfoFactory::make(
            [
                'weatherCondition' => new WeatherCondition(
                    id: 200,
                    main: 'Thunderstorm',
                    description: 'thunderstorm with light rain',
                    iconCode: '11d',
                ),
            ]
        );

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null, $weatherInfo);

        $minPart = self::LINEAR_FOOT_PER_SECOND / 2 * (4 * sqrt(10000.0) + 4 * sqrt(5000.0));
        $maxPart = pow(self::LINEAR_FOOT_PER_SECOND / 2, 2.5) * (4 * sqrt(10000.0) + 4 * sqrt(5000.0));
        $expectedDuration = Duration::fromSeconds((int) (360 + ($minPart + $maxPart) / 2));

        $this->assertEquals($expectedDuration, $serviceDuration->getOptimumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_maximum_duration_with_historical_average_duration_correctly(): void
    {
        $landSqFt = 10000.0;
        $buildingSqFt = 5000.0;
        $historicalAverageDuration = 20;

        $this->propertyDetailsMock->method('getLandSqFt')->willReturn($landSqFt);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn($buildingSqFt);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, $historicalAverageDuration);

        $perimeter = 4 * (sqrt($landSqFt) + sqrt($buildingSqFt));
        $linearFootPerSecond = pow((60 * $historicalAverageDuration - 360) / $perimeter, 1 / 2.5);
        $maximumDurationSeconds = 360 + pow($linearFootPerSecond, 2.5) * $perimeter;

        $expectedDuration = Duration::fromSeconds((int) $maximumDurationSeconds);

        $this->assertEquals($expectedDuration, $serviceDuration->getMaximumDuration());
    }

    /**
     * @test
     */
    public function it_calculates_optimum_duration_with_historical_average_duration_correctly(): void
    {
        $landSqFt = 10000.0;
        $buildingSqFt = 5000.0;
        $historicalAverageDuration = 20;

        $this->propertyDetailsMock->method('getLandSqFt')->willReturn($landSqFt);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn($buildingSqFt);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, $historicalAverageDuration);

        $perimeter = 4 * (sqrt($landSqFt) + sqrt($buildingSqFt));
        $linearFootPerSecond = $serviceDuration->getLFforOptimumDuration();
        $minimum = $linearFootPerSecond * $perimeter;
        $maximum = pow($linearFootPerSecond, 2.5) * $perimeter;

        $optimumDurationSeconds = (360 + ($minimum + $maximum) / 2);
        $expectedDuration = Duration::fromSeconds((int) $optimumDurationSeconds);

        $this->assertEquals($expectedDuration, $serviceDuration->getOptimumDuration());
    }

    /**
     * @test
     */
    public function it_reversely_calculates_the_lf_value_correctly(): void
    {
        $landSqFt = 10000.0;
        $buildingSqFt = 5000.0;

        $this->propertyDetailsMock->method('getLandSqFt')->willReturn($landSqFt);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn($buildingSqFt);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock);
        $optimumDuration = $serviceDuration->getOptimumDuration()->getTotalMinutes();

        $serviceDurationWithHV = new ServiceDuration($this->propertyDetailsMock, $optimumDuration);
        $calculatedLF = $serviceDurationWithHV->getLFforOptimumDuration();

        $expectedLF = self::LINEAR_FOOT_PER_SECOND;
        $tolerance = 0.02;
        $this->assertEqualsWithDelta($expectedLF, $calculatedLF, $tolerance, 'Calculated LF does not match expected value within tolerance.');
    }

    /**
     * @test
     */
    public function it_correctly_uses_custom_lf_for_min_and_max_duration_calculations(): void
    {
        $landSqFt = 10000.0;
        $buildingSqFt = 5000.0;
        $customLF = 1.5;

        $this->propertyDetailsMock->method('getLandSqFt')->willReturn($landSqFt);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn($buildingSqFt);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null, null, $customLF);

        $perimeter = 4 * (sqrt($landSqFt) + sqrt($buildingSqFt));
        $minimumDurationSeconds = 360 + $customLF * $perimeter;
        $maximumDurationSeconds = 360 + pow($customLF, 2.5) * $perimeter;

        $expectedMinimumDuration = Duration::fromSeconds((int) $minimumDurationSeconds);
        $expectedMaximumDuration = Duration::fromSeconds((int) $maximumDurationSeconds);

        $this->assertEquals($expectedMinimumDuration, $serviceDuration->getMinimumDuration(), 'Minimum duration mismatch with custom customLF.');
        $this->assertEquals($expectedMaximumDuration, $serviceDuration->getMaximumDuration(), 'Maximum duration mismatch with custom customLF.');
    }

    /**
     * @test
     */
    public function it_correctly_uses_custom_lf_for_optimum_duration_calculation(): void
    {
        $customLF = 2.5;

        $this->propertyDetailsMock->method('getLandSqFt')->willReturn(10000.0);
        $this->propertyDetailsMock->method('getBuildingSqFt')->willReturn(5000.0);

        $serviceDuration = new ServiceDuration($this->propertyDetailsMock, null, null, $customLF);

        $minPart = $customLF * (4 * sqrt(10000.0) + 4 * sqrt(5000.0));
        $maxPart = pow($customLF, 2.5) * (4 * sqrt(10000.0) + 4 * sqrt(5000.0));
        $expectedDuration = Duration::fromSeconds((int) (360 + ($minPart + $maxPart) / 2));

        $this->assertEquals($expectedDuration, $serviceDuration->getOptimumDuration());
    }
}
