<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Scheduling;

use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\ValueObjects\CustomerPreferences;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;

class PendingServiceFactory extends AbstractFactory
{
    public function single($overrides = []): PendingService
    {
        return new PendingService(
            $overrides['subscriptionId'] ?? $this->faker->randomNumber(6),
            $overrides['plan'] ?? PlanFactory::make(),
            $overrides['customer'] ?? CustomerFactory::make(),
            $overrides['previousAppointment'] ?? AppointmentFactory::make(),
            $overrides['nextServiceDate'] ?? Carbon::today(),
            $overrides['customerPreferences'] ?? new CustomerPreferences(),
            key_exists('previousAppointment', $overrides) ? $overrides['previousAppointment'] : null,
        );
    }
}
