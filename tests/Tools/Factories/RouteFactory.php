<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\RouteOptimization\ValueObjects\RouteConfig;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\Tools\TestValue;

class RouteFactory extends AbstractFactory
{
    public function single($overrides = []): Route
    {
        $routeId = $overrides['id'] ?? $this->faker->randomNumber(6);

        $workEvents = isset($overrides['workEvents'])
            ? $this->makeWorkEventsForRoute($overrides['workEvents'], $routeId)
            : $this->getConsistentWorkEvents($routeId);

        $servicePro = array_key_exists('servicePro', $overrides)
            ? $overrides['servicePro']
            : ServiceProFactory::make([
                'workingHours' => new TimeWindow(
                    Carbon::tomorrow()->hour(TestValue::START_OF_DAY),
                    Carbon::tomorrow()->hour(TestValue::END_OF_DAY),
                ),
            ]);
        $route = new Route(
            id: $routeId,
            officeId: $overrides['officeId'] ?? $this->faker->randomNumber(2),
            date: $overrides['date'] ?? Carbon::now()->startOfDay(),
            servicePro: $servicePro,
            routeType: $overrides['routeType'] ?? RouteType::REGULAR_ROUTE,
            actualCapacityCount: $overrides['actualCapacityCount'] ?? 21,
            config: $overrides['config'] ?? new RouteConfig(),
        );

        $route->addWorkEvents(new Collection($workEvents));

        if (isset($overrides['timeWindow'])) {
            $route->setTimeWindow($overrides['timeWindow']);
        }

        if (isset($overrides['metrics']) && is_array($overrides['metrics'])) {
            foreach ($overrides['metrics'] as $metric) {
                $route->setMetric($metric);
            }
        }

        if (isset($overrides['geometry'])) {
            $route->setGeometry($overrides['geometry']);
        }

        if (isset($overrides['capacity'])) {
            $route->setCapacity($overrides['capacity']);
        }

        return $route;
    }

    private function makeWorkEventsForRoute(Collection|array $workEvents, int $routeId): array
    {
        $newWorkEvents = [];
        foreach ($workEvents as $workEvent) {
            $newWorkEvent = clone $workEvent;
            if (method_exists($newWorkEvent, 'setRouteId')) {
                $newWorkEvent->setRouteId($routeId);
            }
            $newWorkEvents[] = $newWorkEvent;
        }

        return $newWorkEvents;
    }

    /**
     * @return array<WorkEvent>
     */
    private function getConsistentWorkEvents(int $routeId): array
    {
        $day = Carbon::tomorrow()->toDateString();

        $workEvents = [
            [WorkEventType::START_LOCATION, '08:00'],
            [WorkEventType::APPOINTMENT, '08:05', '08:29'],
            [WorkEventType::TRAVEL, '08:30', '08:59'],
            [WorkEventType::APPOINTMENT, '09:00', '09:29'],
            [WorkEventType::BREAK, '09:30', '09:45', '09:30', '10:30'],
            [WorkEventType::TRAVEL, '09:45', '09:59'],
            [WorkEventType::APPOINTMENT, '10:00', '10:29'],
            [WorkEventType::TRAVEL, '10:30', '10:59'],
            [WorkEventType::APPOINTMENT, '11:00', '11:29'],
            [WorkEventType::LUNCH, '11:30', '12:00', '11:00', '12:00'],
            [WorkEventType::TRAVEL, '12:01', '12:05'],
            [WorkEventType::APPOINTMENT, '12:06', '12:29'],
            [WorkEventType::TRAVEL, '12:30', '12:59'],
            [WorkEventType::APPOINTMENT, '13:00', '13:29'],
            [WorkEventType::BREAK, '13:30', '13:45', '13:00', '14:30'],
            [WorkEventType::TRAVEL, '13:46', '13:59'],
            [WorkEventType::APPOINTMENT, '14:00', '14:29'],
            [WorkEventType::TRAVEL, '14:30', '14:59'],
            [WorkEventType::APPOINTMENT, '15:00', '15:29'],
            [WorkEventType::TRAVEL, '15:30', '15:59'],
            [WorkEventType::APPOINTMENT, '16:00', '16:29'],
            [WorkEventType::TRAVEL, '16:30', '16:59'],
            [WorkEventType::APPOINTMENT, '17:00', '17:29'],
            [WorkEventType::END_LOCATION, '17:30'],
        ];
        $workEventsCollection = new Collection();

        foreach ($workEvents as $item) {
            $workEvent = match ($item[0]) {
                WorkEventType::APPOINTMENT => AppointmentFactory::make([
                    'timeWindow' => new TimeWindow(
                        Carbon::parse($day . ' ' . $item[1]),
                        Carbon::parse($day . ' ' . $item[2]),
                    ),
                ]),

                WorkEventType::TRAVEL => TravelFactory::make([
                    'timeWindow' => new TimeWindow(
                        Carbon::parse($day . ' ' . $item[1]),
                        Carbon::parse($day . ' ' . $item[2]),
                    ),
                ]),

                WorkEventType::BREAK => WorkBreakFactory::make([
                    'timeWindow' => new TimeWindow(
                        Carbon::parse($day . ' ' . $item[1]),
                        Carbon::parse($day . ' ' . $item[2]),
                    ),
                    'expectedArrival' => new TimeWindow(
                        Carbon::parse($day . ' ' . $item[3]),
                        Carbon::parse($day . ' ' . $item[4]),
                    ),
                ]),

                WorkEventType::LUNCH => LunchFactory::make([
                    'timeWindow' => new TimeWindow(
                        Carbon::parse($day . ' ' . $item[1]),
                        Carbon::parse($day . ' ' . $item[2]),
                    ),
                    'expectedArrival' => new TimeWindow(
                        Carbon::parse($day . ' ' . $item[3]),
                        Carbon::parse($day . ' ' . $item[4]),
                    ),
                ]),

                WorkEventType::START_LOCATION => StartLocationFactory::make([
                    'startAt' => Carbon::parse($day . ' ' . $item[1]),
                    'location' => new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
                ]),

                WorkEventType::END_LOCATION => EndLocationFactory::make([
                    'startAt' => Carbon::parse($day . ' ' . $item[1]),
                    'location' => new Coordinate(TestValue::MAX_LATITUDE, TestValue::MAX_LONGITUDE),
                ])
            };

            $workEventsCollection->add($workEvent);
        }

        return $this->makeWorkEventsForRoute($workEventsCollection, $routeId);
    }
}
