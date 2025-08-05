<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Factories;

use App\Domain\Tracking\Entities\Events\FleetRouteEvent;
use App\Domain\Tracking\Factories\PlannedAppointmentFactory;
use App\Domain\Tracking\Factories\RouteFactory;
use Carbon\CarbonTimeZone;
use PHPUnit\Framework\TestCase;
use Tests\Tools\TestValue;

class RouteFactoryTest extends TestCase
{
    private RouteFactory $routeFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $appointmentFactory = \Mockery::mock(PlannedAppointmentFactory::class);
        $appointmentFactory->shouldReceive('create')->andReturn(\Tests\Tools\Factories\Tracking\PlannedAppointmentFactory::make());

        $this->routeFactory = new RouteFactory($appointmentFactory);
    }

    /**
     * @test
     */
    public function it_creates_a_route(): void
    {
        $data = $this->getRouteEvents();
        $route = $this->routeFactory->create($data, CarbonTimeZone::create(TestValue::TIME_ZONE));

        $this->assertNotEmpty($route);

        $route->each(function ($appointment) {
            $this->assertInstanceOf(FleetRouteEvent::class, $appointment);
        });

        $this->assertCount(2, $route);
    }

    /**
     * @return mixed[]
     */
    private function getRouteEvents(): array
    {
        return [
            [
                'location' => [
                    'lat' => 30.351305579189788,
                    'lon' => -97.70943845704998,
                ],
                'description' => 'Start',
                'work_event_type' => 'Start Location',
                'scheduled_time_window' => [
                    'end' => '2024-03-11 07:30:00',
                    'start' => '2024-03-11 07:30:00',
                ],
            ],
            [
                'description' => 'Travel',
                'travel_miles' => 1.59,
                'work_event_type' => 'Travel',
                'scheduled_time_window' => [
                    'end' => '2024-03-11 08:48:54',
                    'start' => '2024-03-11 08:45:00',
                ],
            ],
            [
                'location' => [
                    'lat' => 30.33363,
                    'lon' => -97.714622,
                ],
                'priority' => 25,
                'is_locked' => 0,
                'description' => 'Pro Plus',
                'appointment_id' => 27278857,
                'setup_duration' => 3,
                'work_event_type' => 'Appointment',
                'maximum_duration' => null,
                'minimum_duration' => null,
                'service_duration' => 20,
                'expected_time_window' => [
                    'end' => '2024-03-11 23:59:59',
                    'start' => '2024-03-11 00:00:00',
                ],
                'scheduled_time_window' => [
                    'end' => '2024-03-11 09:11:54',
                    'start' => '2024-03-11 08:48:54',
                ],
            ],
            [
                'description' => 'Travel',
                'travel_miles' => 4.17,
                'work_event_type' => 'Travel',
                'scheduled_time_window' => [
                    'end' => '2024-03-11 09:19:41',
                    'start' => '2024-03-11 09:11:54',
                ],
            ],
            [
                'location' => [
                    'lat' => 30.365887,
                    'lon' => -97.673866,
                ],
                'priority' => 25,
                'is_locked' => 0,
                'description' => 'Basic',
                'appointment_id' => 27278845,
                'setup_duration' => 3,
                'work_event_type' => 'Appointment',
                'maximum_duration' => null,
                'minimum_duration' => null,
                'service_duration' => 20,
                'expected_time_window' => [
                    'end' => '2024-03-11 23:59:59',
                    'start' => '2024-03-11 00:00:00',
                ],
                'scheduled_time_window' => [
                    'end' => '2024-03-11 09:42:41',
                    'start' => '2024-03-11 09:19:41',
                ],
            ],
            [
                'description' => '15 Min Break',
                'work_event_type' => 'Break',
                'scheduled_time_window' => [
                    'end' => '2024-03-11 09:57:41',
                    'start' => '2024-03-11 09:42:41',
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->routeFactory);
    }
}
