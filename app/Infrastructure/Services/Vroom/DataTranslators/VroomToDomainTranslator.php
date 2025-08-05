<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\AbstractWorkEvent;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Exceptions\WorkEventNotFoundAfterOptimizationException;
use App\Domain\RouteOptimization\Factories\OptimizationStateFactory;
use App\Domain\RouteOptimization\ValueObjects\EndLocation;
use App\Domain\RouteOptimization\ValueObjects\StartLocation;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\Vroom\Enums\StepType;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Collection;

/**
 * @phpstan-type VroomResponseStep array{
 *      arrival?:int,
 *      description:string,
 *      duration?:int,
 *      distance: int,
 *      id:int,
 *      location:float[],
 *      service?:int,
 *      setup?:int,
 *      type:string,
 *      waiting_time:int
 *  }
 * @phpstan-type VroomResponseRoute array{
 *     description:string,
 *     duration: int,
 *     distance: float,
 *     vehicle:int,
 *     steps:VroomResponseStep[]
 * }
 * @phpstan-type VroomResponse array{
 *     summary: array{
 *         duration: int,
 *         distance: float,
 *     },
 *     routes:VroomResponseRoute[],
 *     unassigned:VroomResponseStep[]
 * }
 */
class VroomToDomainTranslator
{
    public function __construct(
        private OptimizationStateFactory $factory,
    ) {
    }

    /**
     * @param VroomResponse $data
     * @param OptimizationState $previousOptimizationState
     * @param OptimizationStatus $status
     *
     * @return OptimizationState
     */
    public function translate(
        array $data,
        OptimizationState $previousOptimizationState,
        OptimizationStatus $status,
    ): OptimizationState {
        $optimizationState = $this->factory->makeFromOptimizationState(
            $previousOptimizationState,
            OptimizationEngine::VROOM,
            $status,
        );
        $this->setRoutesData($data, $optimizationState);

        return $optimizationState;
    }

    /**
     * @param VroomResponse $data
     * @param Route $route
     *
     * @return Route
     */
    public function translateSingleRoute(array $data, Route $route): Route
    {
        $newRoute = clone $route;
        $timeZone = CarbonTimeZone::instance($route->getTimeWindow()->getStartAt()->getTimezone());
        $workEvents = $newRoute->getAppointments()->merge($newRoute->getMeetings());
        $breaks = $newRoute->getAllBreaks();
        $newRoute->clearWorkEvents();

        if (empty($data['routes'])) {
            return $newRoute;
        }

        /** @var VroomResponseStep[] $routeData */
        $routeData = $data['routes'][0]['steps'];

        $this->addWorkEventsToRoute($routeData, $newRoute, $workEvents, $breaks, $timeZone);

        return $newRoute;
    }

    /**
     * @param VroomResponse $data
     * @param OptimizationState $optimizationState
     */
    private function setRoutesData(array $data, OptimizationState $optimizationState): void
    {
        $allWorkEvents = $optimizationState->getVisitableWorkEvents();

        /** @var VroomResponseStep[] $unassignedData */
        $unassignedData = $data['unassigned'];
        $unassignedAppointments = $this->getUnassignedAppointments($unassignedData, $allWorkEvents);
        $optimizationState->setUnassignedAppointments($unassignedAppointments);

        /** @var VroomResponseRoute[] $routesData */
        $routesData = $data['routes'];
        $this->setRouteData($routesData, $optimizationState, $allWorkEvents);
    }

    /**
     * @param VroomResponseStep[] $data
     * @param Route $route
     * @param Collection $workEvents
     * @param Collection $breaks
     * @param CarbonTimeZone $timeZone
     */
    private function addWorkEventsToRoute(
        array $data,
        Route $route,
        Collection $workEvents,
        Collection $breaks,
        CarbonTimeZone $timeZone
    ): void {
        $travelEventId = 0;

        foreach ($data as $datum) {
            if (!empty($previousJobData) && $previousJobData['duration'] !== $datum['duration']) {
                $this->addTravelEventToRoute($route, $timeZone, ++$travelEventId, $datum, $previousJobData);
            }

            $workEvent = match($datum['type']) {
                StepType::BREAK->value => $this->getBreak($datum, $breaks, $timeZone),
                StepType::JOB->value => $this->getWorkEvent($datum, $workEvents, $timeZone),
                StepType::START->value => $this->getStartLocation($datum, $timeZone),
                StepType::END->value => $this->getEndLocation($datum, $timeZone),

                default => throw new \UnhandledMatchError($datum['type']),
            };

            $route->addWorkEvent($workEvent);
            $this->addWaitingToRoute($route, $datum, $timeZone);
            $previousJobData = $datum;
        }

        $this->setWaitingBeforeStart($route);

        // Add skipped break/reserved time
        $route->addWorkEvents($breaks);
    }

    /**
     * @param VroomResponseStep $data
     * @param Collection $workEvents
     * @param CarbonTimeZone $timeZone
     *
     * @return Appointment|Meeting
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    private function getWorkEvent(array $data, Collection $workEvents, CarbonTimeZone $timeZone): Appointment|Meeting
    {
        $endTimestamp = $data['arrival'] + $data['service'] + $data['setup'];

        /** @var Appointment|Meeting $workEvent */
        $workEvent = $this->getByIdAndRemoveFromCollection($data['id'], $workEvents)
            ->setDuration(Duration::fromSeconds($data['service']))
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp($data['arrival'], $timeZone),
                Carbon::createFromTimestamp($endTimestamp, $timeZone),
            ));

        return $workEvent;
    }

    /**
     * @param VroomResponseStep $data
     * @param Collection $breaks
     * @param CarbonTimeZone $timeZone
     *
     * @return WorkBreak|ReservedTime
     * @throws InvalidTimeWindowException
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    private function getBreak(array $data, Collection $breaks, CarbonTimeZone $timeZone): WorkBreak|ReservedTime
    {
        /** @var WorkBreak|ReservedTime $break */
        $break = $this->getByIdAndRemoveFromCollection($data['id'], $breaks);

        $break->setTimeWindow(new TimeWindow(
            Carbon::createFromTimestamp($data['arrival'], $timeZone),
            Carbon::createFromTimestamp($data['arrival'] + $data['service'], $timeZone),
        ))->setDuration(Duration::fromSeconds($data['service']));

        return $break;
    }

    /**
     * @param VroomResponseStep $data
     * @param CarbonTimeZone $timeZone
     *
     * @return StartLocation
     */
    private function getStartLocation(array $data, CarbonTimeZone $timeZone): StartLocation
    {
        // in vroom the expected order for all coordinates arrays is [lon, lat]
        return new StartLocation(
            Carbon::createFromTimestamp($data['arrival'], $timeZone),
            new Coordinate($data['location'][1], $data['location'][0]),
        );
    }

    /**
     * @param VroomResponseStep $data
     * @param CarbonTimeZone $timeZone
     *
     * @return EndLocation
     */
    private function getEndLocation(array $data, CarbonTimeZone $timeZone): EndLocation
    {
        // in vroom the expected order for all coordinates arrays is [lon, lat]
        return new EndLocation(
            Carbon::createFromTimestamp($data['arrival'], $timeZone),
            new Coordinate($data['location'][1], $data['location'][0]),
        );
    }

    /**
     * @param Route $route
     * @param CarbonTimeZone $timeZone
     * @param int $travelEventId
     * @param VroomResponseStep $currentJobData
     * @param VroomResponseStep $previousJobData
     *
     * @return void
     */
    private function addTravelEventToRoute(
        Route $route,
        CarbonTimeZone $timeZone,
        int $travelEventId,
        array $currentJobData,
        array $previousJobData
    ): void {
        $travelSeconds = $currentJobData['duration'] - $previousJobData['duration'];
        $travelMeters = $currentJobData['distance'] - $previousJobData['distance'];
        $travelEndAt = Carbon::createFromTimestamp($currentJobData['arrival'], $timeZone);
        $travelStartAt = $travelEndAt->clone()->subSeconds($travelSeconds);

        $travel = new Travel(
            Distance::fromMeters($travelMeters),
            new TimeWindow($travelStartAt, $travelEndAt),
            $travelEventId,
        );

        $route->addWorkEvent($travel);
    }

    /**
     * @param VroomResponseRoute[] $data
     * @param OptimizationState $optimizationState
     * @param Collection $allWorkEvents
     *
     * @return void
     */
    private function setRouteData(array $data, OptimizationState $optimizationState, Collection $allWorkEvents): void
    {
        $allBreaks = $this->getAllBreaksGroupedByRouteId($optimizationState);

        foreach ($optimizationState->getRoutes() as $route) {
            $route->clearWorkEvents();
        }
        $timeZone = $optimizationState->getOffice()->getTimeZone();
        foreach ($data as $datum) {
            $route = $this->getRouteForVehicle($optimizationState, $datum);
            $this->setRouteGeometry($route, $datum);
            $this->addWorkEventsToRoute(
                $datum['steps'],
                $route,
                $allWorkEvents,
                $allBreaks[$route->getId()] ?? [],
                $timeZone
            );
        }
    }

    /**
     * @phpstan-ignore-next-line
     */
    private function setRouteGeometry(Route $route, array $datum): void
    {
        if (!isset($datum['geometry'])) {
            return;
        }

        $route->setGeometry((string) $datum['geometry']);
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return array<int, Collection>
     */
    private function getAllBreaksGroupedByRouteId(OptimizationState $optimizationState): array
    {
        $breaks = [];

        foreach ($optimizationState->getRoutes() as $route) {
            $breaks[$route->getId()] = $route->getAllBreaks();
        }

        return $breaks;
    }

    /**
     * @param OptimizationState $optimizationState
     * @param VroomResponseRoute $data
     *
     * @return Route
     */
    private function getRouteForVehicle(OptimizationState $optimizationState, array $data): Route
    {
        $routeCollection = $optimizationState->getRoutes()->filter(
            fn (Route $route) => $route->getId() === $data['vehicle']
        );

        return $routeCollection->first();
    }

    /**
     * @param VroomResponseStep[] $data
     * @param Collection<Appointment> $appointments
     *
     * @return Appointment[]
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    private function getUnassignedAppointments(array $data, Collection $appointments): array
    {
        $unassigned = [];
        foreach ($data as $datum) {
            /** @var Appointment $appointment */
            $appointment = $this->getByIdAndRemoveFromCollection($datum['id'], $appointments);
            $unassigned[] = $appointment;
        }

        return $unassigned;
    }

    /**
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    private function getByIdAndRemoveFromCollection(int $id, Collection $workEvents): AbstractWorkEvent
    {
        foreach ($workEvents as $key => $workEvent) {
            if ($id === $workEvent->getId()) {
                $workEvents->forget([$key]);

                return $workEvent;
            }
        }

        throw WorkEventNotFoundAfterOptimizationException::instance();
    }

    /**
     * @param Route $route
     * @param VroomResponseStep $currentData
     * @param CarbonTimeZone $timeZone
     *
     * @return void
     */
    private function addWaitingToRoute(Route $route, array $currentData, CarbonTimeZone $timeZone): void
    {
        if ((int) $currentData['waiting_time'] === 0) {
            return;
        }

        $startTime = Carbon::createFromTimestamp($currentData['arrival'], $timeZone)
            ->addSeconds($currentData['setup'])
            ->addSeconds($currentData['service']);
        $endTime = $startTime->clone()->addSeconds($currentData['waiting_time']);

        $waitingEvent = new Waiting(new TimeWindow($startTime, $endTime));

        $route->addWorkEvent($waitingEvent);
    }

    private function setWaitingBeforeStart(Route $route): void
    {
        $servicePro = $route->getServicePro();
        $dayStart = $servicePro->getWorkingHours()->getStartAt();
        $routeStart = $route->getStartLocation()->getTimeWindow()->getStartAt();

        $timeGap = $dayStart->diffInSeconds($routeStart);

        if ($timeGap === 0 || $dayStart > $routeStart) {
            return;
        }

        $waiting = new Waiting(new TimeWindow($dayStart, $routeStart));

        $route->addWorkEvent($waiting);
    }
}
