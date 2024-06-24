<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\WebsocketTracking;

use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\Entities\ServicedRoute;

class ProvidedServicesTrackingDataFormatter
{
    /**
     * Formats TreatmentState as an array
     *
     * @param TreatmentState $state
     *
     * @return array<string, mixed>
     */
    public function format(TreatmentState $state): array
    {
        return [
            'routes' => $this->getRoutes($state),
            'summary' => $state->getSummary()->toArray(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function getRoutes(TreatmentState $state): array
    {
        $result = [];

        /** @var ServicedRoute $route */
        foreach ($state->getServicedRoutes() as $route) {
            $result[] = [
                'id' => $route->getId(),
                'tracking_data' => $route->getTrackingData()?->toArray(),
                'actual_stats' => $route->getActualStatsAsArray(),
            ];
        }

        return $result;
    }
}
