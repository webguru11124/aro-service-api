<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\ServiceHistory;
use App\Domain\RouteOptimization\Enums\ServiceType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\Carbon;

class ServiceHistoryFactory extends AbstractFactory
{
    public function single($overrides = []): ServiceHistory
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(5);
        $customerId = $overrides['customerId'] ?? $this->faker->randomNumber(5);
        $serviceType = $overrides['serviceType'] ?? ServiceType::REGULAR;
        $duration = $overrides['duration'] ?? Duration::fromMinutes(20);
        $date = $overrides['date'] ?? Carbon::tomorrow();

        return new ServiceHistory(
            $id,
            $customerId,
            $serviceType,
            $duration,
            $date
        );
    }
}
