<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google\DataTranslators;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Exceptions\WorkEventNotFoundAfterOptimizationException;
use App\Domain\RouteOptimization\Factories\OptimizationStateFactory;
use App\Domain\RouteOptimization\ValueObjects\EndLocation;
use App\Domain\RouteOptimization\ValueObjects\StartLocation;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use Google\Cloud\Optimization\V1\OptimizeToursResponse;
use Google\Cloud\Optimization\V1\ShipmentModel;
use Google\Cloud\Optimization\V1\ShipmentRoute;
use Google\Cloud\Optimization\V1\ShipmentRoute\PBBreak;
use Google\Cloud\Optimization\V1\ShipmentRoute\Transition;
use Google\Cloud\Optimization\V1\ShipmentRoute\Visit;
use Google\Cloud\Optimization\V1\SkippedShipment;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Timestamp;
use Illuminate\Support\Collection;

class GoogleToDomainTranslator
{
    private CarbonTimeZone $timeZone;

    public function __construct(
        private OptimizationStateFactory $factory,
    ) {
    }

    /**
     * @param OptimizeToursResponse $response
     * @param OptimizationState $previousOptimizationState
     * @param OptimizationStatus $status
     *
     * @return OptimizationState
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    public function translate(
        ShipmentModel $shipmentModel,
        OptimizeToursResponse $response,
        OptimizationState $previousOptimizationState,
        OptimizationStatus $status,
    ): OptimizationState {
        $this->timeZone = $previousOptimizationState->getOffice()->getTimeZone();
        $optimizationState = $this->factory->makeFromOptimizationState(
            $previousOptimizationState,
            OptimizationEngine::GOOGLE,
            $status,
        );

        $indexedAppointments = $this->getIndexedAppointments($optimizationState, $shipmentModel);

        $this->setRoutesData($optimizationState, $indexedAppointments, $response->getRoutes());
        $this->setUnassignedAppointments($optimizationState, $indexedAppointments, $response->getSkippedShipments());

        return $optimizationState;
    }

    /**
     * @param OptimizationState $optimizationState
     * @param Appointment[] $indexedAppointments
     * @param RepeatedField $routes
     *
     * @return void
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    private function setRoutesData(OptimizationState $optimizationState, array $indexedAppointments, RepeatedField $routes): void
    {
        $startingRoutes = $optimizationState->getRoutes();
        $optimizationState->setRoutes([]);

        /** @var ShipmentRoute $shipmentRoute */
        foreach ($routes as $shipmentRoute) {
            $routeId = (int) $shipmentRoute->getVehicleLabel();

            // route has been skipped by engine
            if ($routeId === 0) {
                continue;
            }

            /** @var Route $startingRoute */
            $startingRoute = $startingRoutes->first(fn (Route $route) => $routeId === $route->getId());
            $route = $this->buildRoute($startingRoute, $shipmentRoute);

            $this->addAppointmentsToRoute($route, $indexedAppointments, $shipmentRoute->getVisits());
            $this->addBreaksToRoute($route, $startingRoute->getAllBreaks(), $shipmentRoute->getBreaks());
            $this->addTravelsToRoute($route, $shipmentRoute->getTransitions());

            $optimizationState->addRoute($route);
        }
    }

    private function buildRoute(Route $startingRoute, ShipmentRoute $routeData): Route
    {
        $route = new Route(
            (int) $routeData->getVehicleLabel(),
            $startingRoute->getOfficeId(),
            $startingRoute->getDate(),
            $startingRoute->getServicePro(),
            $startingRoute->getRouteType(),
            $startingRoute->getActualCapacityCount(),
        );

        $startLocation = new StartLocation(
            $this->convertTimestamp($routeData->getVehicleStartTime()),
            $startingRoute->getStartLocation()->getLocation(),
        );
        $route->addWorkEvent($startLocation);

        $endLocation = new EndLocation(
            $this->convertTimestamp($routeData->getVehicleEndTime()),
            $startingRoute->getEndLocation()->getLocation(),
        );
        $route->addWorkEvent($endLocation);

        return $route;
    }

    /**
     * @param OptimizationState $optimizationState
     * @param ShipmentModel $shipmentModel
     *
     * @return array<Appointment>
     */
    private function getIndexedAppointments(OptimizationState $optimizationState, ShipmentModel $shipmentModel): array
    {
        $appointments = $optimizationState->getAllAppointments();
        $indexed = [];

        foreach ($shipmentModel->getShipments() as $index => $shipment) {
            $indexed[$index] = $appointments->first(function (Appointment $appointment) use ($shipment) {
                return $appointment->getId() === (int) $shipment->getDeliveries()[0]->getLabel();
            });
        }

        return $indexed;
    }

    /**
     * @param Route $route
     * @param Collection<WorkBreak> $workBreaks
     * @param RepeatedField $breaks
     *
     * @return void
     */
    private function addBreaksToRoute(Route $route, Collection $workBreaks, RepeatedField $breaks): void
    {
        /** @var PBBreak $resultBreak */
        foreach ($breaks as $index => $resultBreak) {
            /** @var WorkBreak $sourceBreak */
            $sourceBreak = $workBreaks->get($index);
            $startAt = $this->convertTimestamp($resultBreak->getStartTime());
            $sourceBreak->setTimeWindow(new TimeWindow(
                $startAt,
                $startAt->clone()->addSeconds($resultBreak->getDuration()->getSeconds())
            ));
            $route->addWorkEvent($sourceBreak);
        }
    }

    private function addTravelsToRoute(Route $route, RepeatedField $transitions): void
    {
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            $startAt = $this->convertTimestamp($transition->getStartTime());

            $waitTime = (int) $transition->getWaitDuration()?->getSeconds();
            if ($waitTime > 0) {
                $waitEvent = new Waiting(
                    new TimeWindow(
                        $startAt->clone(),
                        $startAt->clone()->addSeconds($waitTime)
                    )
                );
                $route->addWorkEvent($waitEvent);
                $startAt->addSeconds($waitTime);
            }

            $startAt->addSeconds((int) $transition->getBreakDuration()?->getSeconds());

            $travel = new Travel(
                Distance::fromMeters($transition->getTravelDistanceMeters()),
                new TimeWindow(
                    $startAt,
                    $startAt->clone()->addSeconds((int) $transition->getTravelDuration()?->getSeconds())
                )
            );
            $route->addWorkEvent($travel);
        }
    }

    /**
     * @param Route $startingRoute
     * @param Appointment[] $indexedAppointments
     * @param RepeatedField $visits
     *
     * @return void
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    private function addAppointmentsToRoute(Route $startingRoute, array $indexedAppointments, RepeatedField $visits): void
    {
        /** @var Visit $visit */
        foreach ($visits as $visit) {
            $index = $visit->getShipmentIndex();

            if (!isset($indexedAppointments[$index])) {
                throw WorkEventNotFoundAfterOptimizationException::instance();
            }

            $appointment = clone $indexedAppointments[$index];
            $appointment->setStartAtAndAdjustEndAt($this->convertTimestamp($visit->getStartTime()));
            $startingRoute->addWorkEvent($appointment);
        }
    }

    /**
     * @param OptimizationState $optimizationState
     * @param Appointment[] $indexedAppointments
     * @param RepeatedField $skippedShipments
     *
     * @return void
     */
    private function setUnassignedAppointments(
        OptimizationState $optimizationState,
        array $indexedAppointments,
        RepeatedField $skippedShipments
    ): void {
        $appointments = [];

        /** @var SkippedShipment $skippedShipment */
        foreach ($skippedShipments as $skippedShipment) {
            $index = $skippedShipment->getIndex();
            $appointments[] = $indexedAppointments[$index];
        }

        $optimizationState->setUnassignedAppointments($appointments);
    }

    private function convertTimestamp(Timestamp $timestamp): CarbonInterface
    {
        return (Carbon::parse($timestamp->toDateTime()))->setTimezone($this->timeZone);
    }

    /**
     * Extracts first route from Optimization response and translates it to domain entity
     *
     * @param OptimizeToursResponse $response
     * @param Route $startingRoute
     *
     * @return Route
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    public function translateSingleRoute(OptimizeToursResponse $response, Route $startingRoute): Route
    {
        if ($response->getRoutes()->count() === 0) {
            return $startingRoute;
        }

        $this->timeZone = $startingRoute->getDate()->timezone;

        $shipmentRoute = $response->getRoutes()[0];
        $route = $this->buildRoute($startingRoute, $shipmentRoute);

        $this->addAppointmentsToRoute($route, $startingRoute->getAppointments()->toArray(), $shipmentRoute->getVisits());
        $this->addBreaksToRoute($route, $startingRoute->getAllBreaks(), $shipmentRoute->getBreaks());
        $this->addTravelsToRoute($route, $shipmentRoute->getTransitions());

        return $route;
    }
}
