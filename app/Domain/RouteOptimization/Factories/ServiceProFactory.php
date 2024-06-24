<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Factories;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class ServiceProFactory
{
    /**
     * @param mixed[] $routeData
     * @param CarbonTimeZone $timezone
     *
     * @return ServicePro
     * @throws InvalidTimeWindowException
     */
    public function make(array $routeData, CarbonTimeZone $timezone): ServicePro
    {
        $date = Carbon::parse($routeData['details']['start_at'], $timezone);
        $serviceProData = $routeData['service_pro'];
        $skills = array_map(fn ($skill) => Skill::tryFromState($skill), $serviceProData['skills'] ?? []);
        $startLocation = $serviceProData['start_location'] ?? $routeData['details']['start_location'];
        $endLocation = $serviceProData['end_location'] ?? $routeData['details']['end_location'];

        $servicePro = new ServicePro(
            id: $serviceProData['id'],
            name: $serviceProData['name'],
            startLocation: new Coordinate($startLocation['lat'], $startLocation['lon']),
            endLocation: new Coordinate($endLocation['lat'], $endLocation['lon']),
            workingHours: new TimeWindow(
                $date->clone()->setTimeFromTimeString($serviceProData['working_hours']['start_at']),
                $date->clone()->setTimeFromTimeString($serviceProData['working_hours']['end_at']),
            ),
            workdayId: $serviceProData['workday_id'],
        );

        $servicePro
            ->addSkills($skills)
            ->setRouteId($routeData['id']);

        return $servicePro;
    }
}
