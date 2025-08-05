<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Responses;

use App\Application\Http\Api\Tracking\V1\Resources\CoordinateResource;
use App\Application\Http\Responses\AbstractResponse;
use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\Entities\ServicedRoute;
use Aptive\Component\Http\HttpStatus;

class FleetRouteResponse extends AbstractResponse
{
    public function __construct(TreatmentState $state)
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);

        $this->setResult([
            'routes' => $this->getRoutes($state),
            'summary' => $state->getSummary()->toArray(),
        ]);
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
                'service_pro_id' => $route->getServicePro()->getId(),
                'service_pro_name' => $route->getServicePro()->getName(),
                'external_id' => $route->getServicePro()->getWorkdayId(),
                'avatar_placement' => $this->getAvatarPlacement($route),
                'appointments' => $this->getAppointments($route),
                'area' => CoordinateResource::collection($route->getArea()->getVertexes()),
                'statistics' => $this->getStats($route),
                'geometry' => $route->getGeometry(),
                'tracking_data' => $route->getTrackingData()?->toArray(),
                'actual_stats' => $route->getActualStatsAsArray(),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, numeric>|null
     */
    private function getAvatarPlacement(ServicedRoute $route): array|null
    {
        if ($route->getAreaCenter()) {
            return [
                'lat' => $route->getAreaCenter()->getLatitude(),
                'lng' => $route->getAreaCenter()->getLongitude(),
            ];
        }

        return null;
    }

    /**
     * @return array<int, mixed>
     */
    private function getAppointments(ServicedRoute $route): array
    {
        $result = [];

        foreach ($route->getScheduledAppointments() as $appointment) {
            $result[] = [
                'id' => $appointment->getId(),
                'lat' => $appointment->getLocation()->getLatitude(),
                'lng' => $appointment->getLocation()->getLongitude(),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getStats(ServicedRoute $route): array|null
    {
        if ($route->getRouteStats() === null) {
            return null;
        }

        $routeStats = $route->getRouteStats();

        return [
            'total_appointments' => $routeStats->getTotalAppointments(),
            'total_service_time_minutes' => $routeStats->getTotalServiceTime()->getTotalMinutes(),
            'total_drive_time_minutes' => $routeStats->getTotalDriveTime()->getTotalMinutes(),
            'total_drive_miles' => $routeStats->getTotalDriveDistance()->getMiles(),
            'average_drive_time_minutes' => $routeStats->getAverageDriveTimeBetweenServices()->getTotalMinutes(),
            'average_drive_miles' => $routeStats->getAverageDriveDistanceBetweenServices()->getMiles(),
        ];
    }
}
