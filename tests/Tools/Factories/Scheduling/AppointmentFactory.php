<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Scheduling;

use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;

class AppointmentFactory extends AbstractFactory
{
    public function single($overrides = []): Appointment
    {
        return new Appointment(
            id: $overrides['id'] ?? $this->faker->randomNumber(),
            initial: $overrides['initial'] ?? false,
            date: $overrides['date'] ?? Carbon::today()->subDays(10),
            dateCompleted: key_exists('dateCompleted', $overrides) ? $overrides['dateCompleted'] : null,
            customer: $overrides['customer'] ?? CustomerFactory::make(),
            duration: $overrides['duration'] ?? Duration::fromMinutes(30),
        );
    }
}
