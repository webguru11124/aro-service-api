<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Factories;

use App\Domain\Tracking\Entities\Events\FleetRouteEvent;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Collection;

class RouteFactory
{
    public function __construct(
        private readonly PlannedAppointmentFactory $appointmentFactory,
    ) {
    }

    /**
     * Create a route from route data
     *
     * @param array<string, mixed> $routeEvents
     *
     * @return Collection<FleetRouteEvent>
     */
    public function create(array $routeEvents, CarbonTimeZone $timeZone): Collection
    {
        $route = new Collection();

        foreach ($routeEvents as $event) {
            if ($event['work_event_type'] === 'Appointment') {
                $route->push($this->appointmentFactory->create($event, $timeZone));
            }
        }

        return $route;
    }
}
