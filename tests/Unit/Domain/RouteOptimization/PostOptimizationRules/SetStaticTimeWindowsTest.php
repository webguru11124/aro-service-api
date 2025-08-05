<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\RouteOptimization\PostOptimizationRules\SetStaticTimeWindows;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;

class SetStaticTimeWindowsTest extends TestCase
{
    private const RANGES = [
        [8, 11],
        [10, 13],
        [12, 15],
        [14, 17],
        [16, 19],
    ];

    private SetStaticTimeWindows $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new SetStaticTimeWindows();
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_calculates_static_time_window(string $startTime, array|null $expectedTimeRange): void
    {
        $day = Carbon::tomorrow();

        $appointment = AppointmentFactory::make([
            'timeWindow' => new TimeWindow(
                $start = Carbon::parse($day->toDateString() . ' ' . $startTime),
                $start->clone()->addMinutes(30)
            ),
        ]);

        $expectedTimeWindow = $expectedTimeRange
            ? new TimeWindow(
                $day->clone()->hour($expectedTimeRange[0]),
                $day->clone()->hour($expectedTimeRange[1])
            )
            : null;

        $result = $this->rule->calculateStaticTimeWindow($appointment);

        $this->assertTrue($expectedTimeWindow?->getStartAt() == $result?->getStartAt());
        $this->assertTrue($expectedTimeWindow?->getEndAt() == $result?->getEndAt());
    }

    public static function dataProvider(): iterable
    {
        yield ['07:49', null];
        yield ['07:51', self::RANGES[0]];
        yield ['08:05', self::RANGES[0]];
        yield ['10:05', self::RANGES[0]];
        yield ['10:40', self::RANGES[1]];
        yield ['11:55', self::RANGES[1]];
        yield ['12:10', self::RANGES[1]];
        yield ['12:35', self::RANGES[2]];
        yield ['13:15', self::RANGES[2]];
        yield ['14:30', self::RANGES[2]];
        yield ['14:31', self::RANGES[3]];
        yield ['16:30', self::RANGES[3]];
        yield ['16:31', self::RANGES[4]];
        yield ['18:59', self::RANGES[4]];
        yield ['19:01', null];
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_id(): void
    {
        $result = $this->rule->id();

        $this->assertEquals('SetStaticTimeWindows', $result);
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_name(): void
    {
        $result = $this->rule->name();

        $this->assertNotEmpty($result);
    }

    /**
     * @test
     *
     * ::getDescription
     */
    public function it_returns_rule_description(): void
    {
        $result = $this->rule->description();

        $this->assertNotEmpty($result);
    }
}
