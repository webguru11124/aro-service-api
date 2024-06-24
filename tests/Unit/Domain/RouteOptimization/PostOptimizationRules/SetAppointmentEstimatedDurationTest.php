<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\PostOptimizationRules\SetAppointmentEstimatedDuration;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Tests\TestCase;

class SetAppointmentEstimatedDurationTest extends TestCase
{
    private SetAppointmentEstimatedDuration $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new SetAppointmentEstimatedDuration();
    }

    /**
     * @test
     *
     * ::generateDurationNotes
     */
    public function it_generates_notes(): void
    {
        /** @var Appointment $appointment */
        $appointment = \Mockery::mock(Appointment::class);
        $appointment->shouldReceive('getMinimumDuration')->andReturn(Duration::fromMinutes(10));
        $appointment->shouldReceive('getMaximumDuration')->andReturn(Duration::fromMinutes(20));
        $appointment->shouldReceive('getDuration')->andReturn(Duration::fromMinutes(15));

        $result = $this->rule->generateDurationNotes(
            $appointment,
            'Some Notes.'
        );

        $this->assertEquals("Some Notes.\nMinimum Duration: 10\nMaximum Duration: 20\nOptimal Duration: 15\n", $result);
    }

    /**
     * @test
     *
     * @dataProvider durationProvider
     *
     * ::roundDuration
     */
    public function it_returns_rounded_duration(int $duration, int $expectedResult): void
    {
        $result = $this->rule->roundDuration($duration);

        $this->assertEquals($expectedResult, $result);
    }

    public static function durationProvider(): iterable
    {
        yield [0, 0];
        yield [11, 10];
        yield [12, 10];
        yield [13, 15];
        yield [14, 15];
        yield [16, 15];
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_id(): void
    {
        $result = $this->rule->id();

        $this->assertEquals('SetAppointmentEstimatedDuration', $result);
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
