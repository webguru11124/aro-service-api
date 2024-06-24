<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\Tools\TestValue;

class AppointmentFactory extends AbstractFactory
{
    public function single($overrides = []): Appointment
    {
        $startAt = Carbon::tomorrow()->hour(TestValue::START_OF_DAY);
        $timeWindow = $overrides['timeWindow'] ?? new TimeWindow(
            $startAt,
            $startAt->clone()->addMinutes(TestValue::APPOINTMENT_DURATION),
        );
        $location = $overrides['location'] ?? new Coordinate(
            $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
            $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
        );
        $routeId = $overrides['routeId'] ?? TestValue::ROUTE_ID;
        $expectedArrival = $overrides['expectedArrival'] ?? new TimeWindow(
            Carbon::tomorrow()->hour(TestValue::START_OF_DAY),
            Carbon::tomorrow()->hour(TestValue::END_OF_DAY),
        );
        $customerId = $overrides['customerId'] ?? $this->faker->randomNumber(5);
        $officeId = $overrides['officeId'] ?? TestValue::OFFICE_ID;

        $appointment = (new Appointment(
            $overrides['id'] ?? $this->faker->randomNumber(6),
            $overrides['description'] ?? $this->faker->text(),
            $location,
            $overrides['notified'] ?? false,
            $officeId,
            $customerId,
            $overrides['preferredTechId'] ?? $this->faker->randomNumber(6),
            $overrides['skills'] ?? collect([new Skill(Skill::AA)]),
        ))
            ->setPriority($overrides['priority'] ?? 60)
            ->setTimeWindow($timeWindow)
            ->setRouteId($routeId)
            ->setExpectedArrival($expectedArrival);

        if (isset($overrides['duration'])) {
            $appointment->setDuration($overrides['duration']);
        }

        if (isset($overrides['setupDuration'])) {
            $appointment->setSetupDuration($overrides['setupDuration']);
        }

        return $appointment;
    }
}
