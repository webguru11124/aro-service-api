<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Tracking\V1\Responses;

use App\Application\Http\Api\Tracking\V1\Responses\FleetRouteResponse;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Tests\TestCase;
use Tests\Tools\Factories\Tracking\TreatmentStateFactory;
use Tests\Tools\Factories\Tracking\ServicedRouteFactory;
use Tests\Traits\AssertArrayHasAllKeys;

class FleetRouteResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $response = new FleetRouteResponse(
            TreatmentStateFactory::make([
                'servicedRoutes' => collect(ServicedRouteFactory::many(2)),
            ])
        );

        $responseData = $response->getData(true);
        $this->assertArrayHasAllKeys([
            'result' => [
                'routes' => [
                    [
                        'id',
                        'service_pro_id',
                        'service_pro_name',
                        'external_id',
                        'avatar_placement' => [
                            'lat',
                            'lng',
                        ],
                        'appointments' => [
                            [
                                'id',
                                'lat',
                                'lng',
                            ],
                        ],
                        'area' => [
                            [
                                'lat',
                                'lng',
                            ],
                        ],
                        'tracking_data',
                        'statistics' => [
                            'total_appointments',
                            'total_service_time_minutes',
                            'total_drive_time_minutes',
                            'total_drive_miles',
                            'average_drive_time_minutes',
                            'average_drive_miles',
                        ],
                        'actual_stats' => [
                            'total_appointments',
                            'total_service_time_minutes',
                            'total_drive_time_minutes',
                            'total_drive_miles',
                            'average_drive_time_minutes',
                            'average_drive_miles',
                            'route_adherence',
                            'at_risk',
                            'completion_percentage',
                        ],
                    ],
                ],
                'summary' => [
                    'total_routes',
                    'total_appointments',
                    'total_drive_time_minutes',
                    'total_drive_miles',
                    'total_service_time_minutes',
                    'appointments_per_gallon',
                    'total_routes_actual',
                    'total_appointments_actual',
                    'total_drive_time_minutes_actual',
                    'total_drive_miles_actual',
                    'total_service_time_minutes_actual',
                    'appointments_per_gallon_actual',
                ],
            ],
        ], $responseData);
    }

    /**
     * @test
     */
    public function it_creates_correct_response_with_empty_fleet_routes(): void
    {
        $response = new FleetRouteResponse(
            TreatmentStateFactory::make([
                'servicedRoutes' => collect(),
            ])
        );

        $responseData = $response->getData(true);
        $this->assertArrayHasAllKeys([
            'result' => [
                'routes',
                'summary',
            ],
        ], $responseData);
    }

    /**
     * @test
     */
    public function it_creates_correct_avatar_placement_when_area_center_exists(): void
    {
        $route = ServicedRouteFactory::make([
            'areaCenter' => new Coordinate(10.0, 20.0),
        ]);
        $response = new FleetRouteResponse(
            TreatmentStateFactory::make([
                'servicedRoutes' => collect([$route]),
            ])
        );

        $responseData = $response->getData(true);
        $this->assertNotNull($responseData['result']['routes'][0]['avatar_placement']);
        $this->assertArrayHasKey('lat', $responseData['result']['routes'][0]['avatar_placement']);
        $this->assertArrayHasKey('lng', $responseData['result']['routes'][0]['avatar_placement']);
    }
}
