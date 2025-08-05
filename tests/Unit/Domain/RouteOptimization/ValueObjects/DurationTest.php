<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterval;
use Tests\TestCase;
use Tests\Traits\VroomDataAndObjects;

class DurationTest extends TestCase
{
    use VroomDataAndObjects;

    /**
     * @test
     */
    public function create_from_seconds(): void
    {
        $actual = Duration::fromSeconds(self::TIMESTAMP_25_MINUTES);
        $this->assertEquals($this->domainDuration25Minutes(), $actual);
    }

    /**
     * @dataProvider minutesDataProvider
     *
     * @param Duration $duration
     * @param $expectedResult
     *
     * @return void
     *
     * @test
     */
    public function get_total_minutes_method(Duration $duration, $expectedResult): void
    {
        $this->assertEquals($expectedResult, $duration->getTotalMinutes());
    }

    /**
     * @dataProvider secondsDataProvider
     *
     * @param Duration $duration
     * @param $expectedResult
     *
     * @return void
     *
     * @test
     */
    public function get_total_seconds_method(Duration $duration, $expectedResult): void
    {
        $this->assertEquals($expectedResult, $duration->getTotalSeconds());
    }

    /**
     * @test
     */
    public function set_duration_in_seconds(): void
    {
        $duration = $this->getSecondsDuration();

        $this->assertEquals(1132, $duration->getTotalSeconds());
    }

    /**
     * @test
     */
    public function invalid__exception(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->getInvalidDuration();
    }

    private function getInvalidDuration(): void
    {
        new Duration(new CarbonInterval('P1Y2M3DT4H5M6S'));
    }

    private function getValidDuration(): Duration
    {
        return new Duration(new CarbonInterval('P3DT4H5M'));
    }

    private function getSecondsDuration(): Duration
    {
        return new Duration(CarbonInterval::seconds(1132));
    }

    public static function minutesDataProvider(): iterable
    {
        $minutes = 60;
        $hours = 1;
        $seconds = 3600;

        yield 'from hours' => [
            new Duration(CarbonInterval::hours($hours)),
            $minutes,
        ];
        yield 'from minutes' => [
            new Duration(CarbonInterval::minutes($minutes)),
            $minutes,
        ];
        yield 'from seconds' => [
            new Duration(CarbonInterval::seconds($seconds)),
            $minutes,
        ];
    }

    public static function secondsDataProvider(): iterable
    {
        $minutes = 60;
        $hours = 1;
        $seconds = 3600;

        yield 'from hours' => [
            new Duration(CarbonInterval::hours($hours)),
            $seconds,
        ];
        yield 'from minutes' => [
            new Duration(CarbonInterval::minutes($minutes)),
            $seconds,
        ];
        yield 'from seconds' => [
            new Duration(CarbonInterval::seconds($seconds)),
            $seconds,
        ];
    }

    /**
     * @test
     */
    public function it_can_be_increased(): void
    {
        $numbers = [
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(2),
        ];
        $initial = Duration::fromMinutes($numbers[0]);
        $increment1 = Duration::fromMinutes($numbers[1]);
        $increment2 = Duration::fromMinutes($numbers[2]);

        $expected = Duration::fromMinutes(array_sum($numbers));
        $result = $initial->increase($increment1)->increase($increment2);

        $this->assertEquals($expected, $result);
        $this->assertEquals($numbers[0], $initial->getTotalMinutes());
        $this->assertEquals($numbers[1], $increment1->getTotalMinutes());
        $this->assertEquals($numbers[2], $increment2->getTotalMinutes());
    }

    /**
     * @test
     *
     * @dataProvider decreaseValuesProvider
     */
    public function it_can_be_decreased(int $initialValue, int $decrement, int $expectedValue): void
    {
        $initial = Duration::fromMinutes($initialValue);

        $result = $initial->decrease(Duration::fromMinutes($decrement));

        $this->assertEquals($expectedValue, $result->getTotalMinutes());
    }

    public static function decreaseValuesProvider(): iterable
    {
        yield [10, 5, 5];
        yield [5, 5, 0];
        yield [5, 10, 0];
    }

    /**
     * @test
     *
     * @dataProvider formatDataProvider
     */
    public function it_returns_correctly_formatted_string(Duration $duration, string $expected): void
    {
        $this->assertEquals($expected, $duration->format());
    }

    public static function formatDataProvider(): iterable
    {
        yield [Duration::fromMinutes(10), '0h 10m'];
        yield [Duration::fromMinutes(60), '1h 0m'];
        yield [Duration::fromMinutes(90), '1h 30m'];
    }

    /**
     * @test
     *
     * @dataProvider totalHoursDataProvider
     */
    public function get_total_hours(Duration $duration, int $expectedHours): void
    {
        $this->assertEquals($expectedHours, $duration->getTotalHours());
    }

    public static function totalHoursDataProvider(): iterable
    {
        yield [Duration::fromMinutes(20), 0];
        yield [Duration::fromMinutes(60), 1];
        yield [Duration::fromMinutes(119), 1];
        yield [Duration::fromMinutes(121), 2];
        yield [Duration::fromSeconds(1), 0];
        yield [Duration::fromSeconds(3599), 0];
        yield [Duration::fromSeconds(3601), 1];
    }
}
