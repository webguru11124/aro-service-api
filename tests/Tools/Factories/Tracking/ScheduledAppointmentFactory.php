<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\Tracking\Entities\ScheduledAppointment;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;

class ScheduledAppointmentFactory extends AbstractFactory
{
    public function single($overrides = []): ScheduledAppointment
    {
        $startAt = Carbon::today()->setHour($this->faker->numberBetween(8, 18));
        $serviceTimeWindow = $overrides['serviceTimeWindow'] ?? new TimeWindow(
            $startAt->clone(),
            $startAt->clone()->addMinutes(20),
        );
        $expectedTimeWindow = $overrides['expectedTimeWindow'] ?? new TimeWindow(
            $startAt->clone(),
            $startAt->clone()->addMinutes(20),
        );

        return new ScheduledAppointment(
            id: $overrides['id'] ?? $this->faker->randomNumber(),
            date: $overrides['date'] ?? Carbon::today(),
            serviceTimeWindow: $serviceTimeWindow,
            expectedTimeWindow: $expectedTimeWindow,
            dateComplete: $overrides['dateComplete'] ?? Carbon::today(),
            customer: CustomerFactory::make(),
        );
    }
}
