<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Entities\Events;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\Tracking\Entities\Events\PlannedAppointment;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AppointmentTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_tracking_appointment(): void
    {
        $data = [
            'id' => 15,
            'timeWindow' => new TimeWindow(
                Carbon::now(),
                Carbon::now()->addMinutes(30)
            ),
            'location' => new Coordinate(39.8, -115.4),
        ];

        $appointment = new PlannedAppointment(...$data);

        $this->assertEquals($data['id'], $appointment->getId());
        $this->assertEquals($data['timeWindow'], $appointment->getTimeWindow());
        $this->assertEquals($data['location'], $appointment->getLocation());
    }
}
