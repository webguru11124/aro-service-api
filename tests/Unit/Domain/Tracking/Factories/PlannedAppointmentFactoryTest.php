<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Factories;

use App\Domain\Tracking\Factories\PlannedAppointmentFactory;
use Carbon\CarbonTimeZone;
use PHPUnit\Framework\TestCase;
use Tests\Tools\TestValue;

class PlannedAppointmentFactoryTest extends TestCase
{
    private PlannedAppointmentFactory $appointmentFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointmentFactory = new PlannedAppointmentFactory();
    }

    /**
     * @test
     */
    public function it_creates_appointment(): void
    {
        $data = $this->getScheduleData();

        $appointment = $this->appointmentFactory->create($data, CarbonTimeZone::create(TestValue::TIME_ZONE));

        $this->assertEquals($data['appointment_id'], $appointment->getId());
        $this->assertEquals($data['scheduled_time_window']['start'], $appointment->getTimeWindow()->getStartAt()->format('Y-m-d H:i:s'));
        $this->assertEquals($data['scheduled_time_window']['end'], $appointment->getTimeWindow()->getEndAt()->format('Y-m-d H:i:s'));
        $this->assertEquals($data['location']['lat'], $appointment->getLocation()->getLatitude());
        $this->assertEquals($data['location']['lon'], $appointment->getLocation()->getLongitude());
    }

    /**
     * @return mixed[]
     */
    private function getScheduleData(): array
    {
        return [
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
        ];
    }
}
