<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Resources;

use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var RouteStats $routeStats */
        $routeStats = $this->resource;

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
