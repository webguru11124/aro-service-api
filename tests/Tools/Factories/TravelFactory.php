<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;

class TravelFactory extends AbstractFactory
{
    public function single($overrides = []): Travel
    {
        return new Travel(
            $overrides['distance'] ?? Distance::fromMeters($this->faker->randomNumber(4)),
            $overrides['timeWindow'] ?? new TimeWindow(Carbon::tomorrow(), Carbon::tomorrow()->addMinutes(15)),
            $overrides['id'] ?? $this->faker->randomNumber(6),
        );
    }
}
