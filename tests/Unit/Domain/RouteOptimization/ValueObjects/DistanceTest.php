<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Distance;
use Tests\TestCase;

class DistanceTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider fromMetersDataProvider
     */
    public function create_from_meters(float $meters, float $miles, int $intMeters, float $kilometers): void
    {
        $instance = Distance::fromMeters($meters);

        $this->assertEquals($miles, $instance->getMiles());
        $this->assertEquals($meters, $instance->getMeters());
        $this->assertEquals($intMeters, $instance->getIntMeters());
        $this->assertEquals($kilometers, $instance->getKilometers());
    }

    public static function fromMetersDataProvider(): iterable
    {
        yield [
            'meters' => 3000,
            'miles' => 1.86,
            'int_meters' => 3000,
            'kilometers' => 3,
        ];
        yield [
            'meters' => 5500.5,
            'miles' => 3.42,
            'int_meters' => 5500,
            'kilometers' => 5.5,
        ];
        yield [
            'meters' => 0,
            'miles' => 0,
            'int_meters' => 0,
            'kilometers' => 0,
        ];
    }

    /**
     * @test
     *
     * @dataProvider fromKilometersDataProvider
     */
    public function create_from_kilometers(float $kilometers, float $miles, float $meters, int $intMeters): void
    {
        $instance = Distance::fromKilometers($kilometers);

        $this->assertEquals($miles, $instance->getMiles());
        $this->assertEquals($meters, $instance->getMeters());
        $this->assertEquals($intMeters, $instance->getIntMeters());
        $this->assertEquals(round($kilometers, 2), $instance->getKilometers());
    }

    public static function fromKilometersDataProvider(): iterable
    {
        yield [
            'kilometers' => 3,
            'miles' => 1.86,
            'meters' => 3000,
            'int_meters' => 3000,
        ];
        yield [
            'kilometers' => 5.5005,
            'miles' => 3.42,
            'meters' => 5500.5,
            'int_meters' => 5500,
        ];
        yield [
            'kilometers' => 0,
            'miles' => 0,
            'meters' => 0,
            'int_meters' => 0,
        ];
    }

    /**
     * @test
     *
     * @dataProvider fromMilesDataProvider
     */
    public function create_from_miles(float $miles, float $meters, int $intMeters, float $kilometers): void
    {
        $instance = Distance::fromMiles($miles);

        $this->assertEquals($miles, $instance->getMiles());
        $this->assertEquals($meters, $instance->getMeters());
        $this->assertEquals($intMeters, $instance->getIntMeters());
        $this->assertEquals($kilometers, $instance->getKilometers());
    }

    public static function fromMilesDataProvider(): iterable
    {
        yield [
            'miles' => 21,
            'meters' => 33796.14,
            'int_meters' => 33796,
            'kilometers' => 33.8,
        ];
        yield [
            'miles' => 66.2,
            'meters' => 106538.31,
            'int_meters' => 106538,
            'kilometers' => 106.54,
        ];
        yield [
            'miles' => 0,
            'meters' => 0,
            'int_meters' => 0,
            'kilometers' => 0,
        ];
    }
}
