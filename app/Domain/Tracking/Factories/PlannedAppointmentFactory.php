<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Factories;

use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\Tracking\Entities\Events\PlannedAppointment;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class PlannedAppointmentFactory
{
    /**
     * Creates an appointment from the given data
     *
     * @param array<string, mixed> $appointmentData
     * @param CarbonTimeZone $timeZone
     *
     * @return PlannedAppointment
     * @throws InvalidTimeWindowException
     */
    public function create(array $appointmentData, CarbonTimeZone $timeZone): PlannedAppointment
    {
        return new PlannedAppointment(
            $appointmentData['appointment_id'],
            new TimeWindow(
                Carbon::createFromTimeString($appointmentData['scheduled_time_window']['start'], $timeZone),
                Carbon::createFromTimeString($appointmentData['scheduled_time_window']['end'], $timeZone),
            ),
            new Coordinate(
                $appointmentData['location']['lat'] ?? $appointmentData['latitude'],
                $appointmentData['location']['lon'] ?? $appointmentData['longitude']
            ),
        );
    }
}
