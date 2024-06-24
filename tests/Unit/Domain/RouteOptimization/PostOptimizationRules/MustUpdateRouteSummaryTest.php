<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\RouteOptimization\PostOptimizationRules\MustUpdateRouteSummary;
use App\Domain\RouteOptimization\ValueObjects\RouteSummary;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\Carbon;
use Tests\TestCase;

class MustUpdateRouteSummaryTest extends TestCase
{
    private MustUpdateRouteSummary $mustUpdateRouteSummary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mustUpdateRouteSummary = new MustUpdateRouteSummary();
    }

    /**
     * @test
     *
     * @dataProvider formatSummaryStringDataProvider
     */
    public function it_formats_summary_string(RouteSummary $routeSummary, string $expected): void
    {
        $result = $this->mustUpdateRouteSummary->formatRouteSummary($routeSummary);

        $this->assertEquals($expected, $result);
    }

    public static function formatSummaryStringDataProvider(): iterable
    {
        $tz = 'PST';
        $date = Carbon::parse('2022-02-24 08:00:00', $tz);

        yield [
            new RouteSummary(
                drivingTime: Duration::fromMinutes(90),
                servicingTime: Duration::fromMinutes(90),
                totalWorkingTime: Duration::fromMinutes(90),
                asOf: $date,
                excludeFirstAppointment: true
            ),
            'ARO Summary: Driving: 1h 30m. Servicing: 1h 30m. Working: 1h 30m. Exclude first Appt: True. As of: Feb 24, 08:00AM PST.',
        ];
        yield [
            new RouteSummary(
                drivingTime: null,
                servicingTime: Duration::fromMinutes(90),
                totalWorkingTime: Duration::fromMinutes(90),
                asOf: $date,
                excludeFirstAppointment: true
            ),
            'ARO Summary: Servicing: 1h 30m. Working: 1h 30m. Exclude first Appt: True. As of: Feb 24, 08:00AM PST.',
        ];
        yield [
            new RouteSummary(
                drivingTime: Duration::fromMinutes(90),
                servicingTime: null,
                totalWorkingTime: Duration::fromMinutes(90),
                asOf: $date,
                excludeFirstAppointment: true
            ),
            'ARO Summary: Driving: 1h 30m. Working: 1h 30m. Exclude first Appt: True. As of: Feb 24, 08:00AM PST.',
        ];
        yield [
            new RouteSummary(
                drivingTime: Duration::fromMinutes(90),
                servicingTime: Duration::fromMinutes(90),
                totalWorkingTime: null,
                asOf: $date,
                excludeFirstAppointment: true
            ),
            'ARO Summary: Driving: 1h 30m. Servicing: 1h 30m. Exclude first Appt: True. As of: Feb 24, 08:00AM PST.',
        ];
        yield [
            new RouteSummary(
                drivingTime: null,
                servicingTime: null,
                totalWorkingTime: null,
                asOf: $date,
                excludeFirstAppointment: false
            ),
            'ARO Summary: Exclude first Appt: False. As of: Feb 24, 08:00AM PST.',
        ];
        yield [
            new RouteSummary(
                drivingTime: null,
                servicingTime: null,
                totalWorkingTime: null,
                asOf: Carbon::parse('2022-02-24 23:00:00', $tz),
                excludeFirstAppointment: false
            ),
            'ARO Summary: Exclude first Appt: False. As of: Feb 24, 11:00PM PST.',
        ];
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_id(): void
    {
        $result = $this->mustUpdateRouteSummary->id();

        $this->assertEquals('MustUpdateRouteSummary', $result);
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_name(): void
    {
        $result = $this->mustUpdateRouteSummary->name();

        $this->assertNotEmpty($result);
    }

    /**
     * @test
     *
     * ::getDescription
     */
    public function it_returns_rule_description(): void
    {
        $result = $this->mustUpdateRouteSummary->description();

        $this->assertNotEmpty($result);
    }
}
