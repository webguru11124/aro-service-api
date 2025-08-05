<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\RegularSpotStrategy;
use Carbon\Carbon;
use Tests\Tools\TestValue;

class SpotFactory extends AbstractFactory
{
    public function single($overrides = []): Spot
    {
        $strategy = $overrides['strategy'] ?? new RegularSpotStrategy();
        $id = $overrides['id'] ?? TestValue::SPOT_ID;
        $officeId = $overrides['officeId'] ?? TestValue::OFFICE_ID;
        $routeId = $overrides['routeId'] ?? TestValue::ROUTE_ID;
        $timeWindow = $overrides['timeWindow'] ?? new TimeWindow(
            Carbon::tomorrow()->hour(8),
            Carbon::tomorrow()->hour(8)->minute(29),
        );
        $blockReason = $overrides['blockReason'] ?? $this->faker->text(16);
        $previousCoordinates = $overrides['previousCoordinates'] ?? new Coordinate(
            TestValue::LATITUDE,
            TestValue::LONGITUDE
        );
        $nextCoordinates = $overrides['nextCoordinates'] ?? new Coordinate(
            TestValue::LATITUDE,
            TestValue::LONGITUDE
        );

        return new Spot(
            strategy: $strategy,
            id: $id,
            officeId: $officeId,
            routeId: $routeId,
            timeWindow: $timeWindow,
            blockReason: $blockReason,
            previousCoordinates: $previousCoordinates,
            nextCoordinates: $nextCoordinates
        );
    }
}
