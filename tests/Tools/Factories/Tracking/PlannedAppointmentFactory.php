<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\Tracking\Entities\Events\PlannedAppointment;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;

class PlannedAppointmentFactory extends AbstractFactory
{
    public const MIN_LATITUDE = 39.8;
    public const MAX_LATITUDE = 40.1;
    public const MIN_LONGITUDE = -115.4;
    public const MAX_LONGITUDE = -110.7;
    public const SERVICE_MINUTES = 30;

    public function single($overrides = []): PlannedAppointment
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(6);
        $timeWindow = $overrides['time_window'] ?? new TimeWindow(
            Carbon::now(),
            Carbon::now()->addMinutes(self::SERVICE_MINUTES)
        );
        $location = $overrides['location'] ?? new Coordinate(
            $this->faker->latitude(self::MIN_LATITUDE, self::MAX_LATITUDE),
            $this->faker->longitude(self::MIN_LONGITUDE, self::MAX_LONGITUDE),
        );

        return new PlannedAppointment(
            $id,
            $timeWindow,
            $location
        );
    }
}
