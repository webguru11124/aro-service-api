<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\Tracking\ValueObjects\RouteCompletionStats;
use Tests\TestCase;

class RouteCompletionStatsTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'routeAdherence' => 96.5,
            'totalAppointments' => 16,
            'totalServiceTime' => Duration::fromMinutes(550),
            'atRisk' => false,
            'completionPercentage' => 90,
        ];
    }

    /**
     * @test
     */
    public function it_returns_expected_getters(): void
    {
        $stats = new RouteCompletionStats(...$this->data);

        $this->assertEquals($this->data['routeAdherence'], $stats->getRouteAdherence());
        $this->assertEquals($this->data['totalAppointments'], $stats->getTotalAppointments());
        $this->assertEquals($this->data['totalServiceTime']->getTotalMinutes(), $stats->getTotalServiceTime()->getTotalMinutes());
        $this->assertEquals($this->data['atRisk'], $stats->isAtRisk());
        $this->assertEquals($this->data['completionPercentage'], $stats->getCompletionPercentage());
    }

    /**
     * @test
     */
    public function it_correctly_converts_to_array(): void
    {
        $stats = new RouteCompletionStats(...$this->data);

        $expectedArray = [
            'route_adherence' => $this->data['routeAdherence'],
            'total_appointments' => $this->data['totalAppointments'],
            'total_service_time_minutes' => $this->data['totalServiceTime']->getTotalMinutes(),
            'completion_percentage' => $this->data['completionPercentage'],
        ];

        $this->assertEquals($expectedArray, $stats->toArray());
    }
}
