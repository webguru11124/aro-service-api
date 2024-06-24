<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\AroBlockedStrategy;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\BucketSpotStrategy;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\RegularSpotStrategy;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\SpotStrategy;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;

class SpotFactory
{
    private const ARO_PREFIX = '\ARO {';
    private const BUCKET_PREFIX = 'Bucket Route';

    public function makeSpot(
        PestRoutesSpot $pestRoutesSpot,
        PestRoutesRoute $pestRoutesRoute
    ): Spot {
        $prevCoordinates = $pestRoutesSpot->previousLatitude && $pestRoutesSpot->previousLongitude
            ? new Coordinate($pestRoutesSpot->previousLatitude, $pestRoutesSpot->previousLongitude)
            : null;

        $nextCoordinates = $pestRoutesSpot->nextLatitude && $pestRoutesSpot->nextLongitude
            ? new Coordinate($pestRoutesSpot->nextLatitude, $pestRoutesSpot->nextLongitude)
            : null;

        return new Spot(
            strategy: $this->resolveStrategy($pestRoutesSpot, $pestRoutesRoute),
            id: $pestRoutesSpot->id,
            officeId: $pestRoutesSpot->officeId,
            routeId: $pestRoutesRoute->id,
            timeWindow: new TimeWindow(Carbon::instance($pestRoutesSpot->start), Carbon::instance($pestRoutesSpot->end)),
            blockReason: html_entity_decode((string) $pestRoutesSpot->blockReason),
            previousCoordinates: $prevCoordinates,
            nextCoordinates: $nextCoordinates
        );
    }

    private function resolveStrategy(
        PestRoutesSpot $pestRoutesSpot,
        PestRoutesRoute $pestRoutesRoute
    ): SpotStrategy {
        if (
            $pestRoutesSpot->capacity === 0
            && !empty($pestRoutesSpot->blockReason)
            && str_starts_with($pestRoutesSpot->blockReason, self::ARO_PREFIX)
        ) {
            return new AroBlockedStrategy();
        }

        if (str_starts_with($pestRoutesRoute->title, self::BUCKET_PREFIX)) {
            return new BucketSpotStrategy();
        }

        return new RegularSpotStrategy();
    }
}
