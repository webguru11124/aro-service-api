<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Factories;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class ServiceProFactory
{
    /**
     * Creates ServicePro object from data array
     *
     * @param array<string, mixed> $routeData
     * @param CarbonTimeZone $timeZone
     *
     * @return ServicePro
     * @throws InvalidTimeWindowException
     */
    public function create(array $routeData, CarbonTimeZone $timeZone): ServicePro
    {
        $startDay = Carbon::parse($routeData['details']['start_at'], $timeZone);
        $startDate = Carbon::createFromTimeString($routeData['service_pro']['working_hours']['start_at'], $timeZone)->setDateFrom($startDay);
        $endDate = Carbon::createFromTimeString($routeData['service_pro']['working_hours']['end_at'], $timeZone)->setDateFrom($startDay);

        return new ServicePro(
            id: $routeData['service_pro']['id'],
            name: $routeData['service_pro']['name'],
            startLocation: new Coordinate(
                $routeData['details']['start_location']['lat'],
                $routeData['details']['start_location']['lon']
            ),
            endLocation: new Coordinate(
                $routeData['details']['end_location']['lat'],
                $routeData['details']['end_location']['lon']
            ),
            workingHours: new TimeWindow(
                $startDate,
                $endDate,
            ),
            workdayId: $routeData['service_pro']['workday_id'] ?? null,
        );
    }
}
