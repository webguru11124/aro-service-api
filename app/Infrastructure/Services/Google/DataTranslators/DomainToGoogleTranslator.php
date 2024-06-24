<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google\DataTranslators;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Infrastructure\Services\Google\DataTranslators\Transformers\AppointmentTransformer;
use App\Infrastructure\Services\Google\DataTranslators\Transformers\DateTimeTransformer;
use App\Infrastructure\Services\Google\DataTranslators\Transformers\RouteTransformer;
use Google\Cloud\Optimization\V1\Shipment;
use Google\Cloud\Optimization\V1\ShipmentModel;
use Google\Cloud\Optimization\V1\Vehicle;
use Illuminate\Support\Collection;

class DomainToGoogleTranslator
{
    public function __construct(
        private readonly AppointmentTransformer $appointmentTransformer,
        private readonly DateTimeTransformer $dateTimeTransformer,
        private readonly RouteTransformer $routeTransformer,
    ) {
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return ShipmentModel
     */
    public function translate(OptimizationState $optimizationState): ShipmentModel
    {
        $dateTimeTransformer = new DateTimeTransformer();
        $startTime = $dateTimeTransformer->transform($optimizationState->getOptimizationTimeFrame()->getStartAt());
        $endTime = $dateTimeTransformer->transform($optimizationState->getOptimizationTimeFrame()->getEndAt());

        return (new ShipmentModel())
            ->setShipments($this->getShipments($optimizationState))
            ->setVehicles($this->getVehicles($optimizationState))
            ->setGlobalStartTime($startTime)
            ->setGlobalEndTime($endTime)
            ->setMaxActiveVehicles($optimizationState->getRoutes()->count());
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return Shipment[]
     */
    private function getShipments(OptimizationState $optimizationState): array
    {
        $appointmentTransformer = new AppointmentTransformer();
        $serviceProSkills = [];

        /** @var Route $route */
        foreach ($optimizationState->getRoutes() as $route) {
            $serviceProSkills[] = $route->getServicePro()->getSkills()
                ->map(fn (Skill $skill) => $skill->value)
                ->toArray();
        }

        $shipments = [];

        foreach ($optimizationState->getAllAppointments() as $appointment) {
            $shipment = $appointmentTransformer->transform($appointment);
            $vehicleIndexes = [];

            foreach ($serviceProSkills as $index => $serviceProSkillsSet) {
                $appointmentSkills = $appointment->getSkills()->map(fn (Skill $skill) => $skill->value)->toArray();

                if (array_intersect($appointmentSkills, $serviceProSkillsSet)) {
                    $vehicleIndexes[] = $index;
                }
            }

            $shipment->setAllowedVehicleIndices($vehicleIndexes);
            $shipments[] = $shipment;
        }

        return $shipments;
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return Vehicle[]
     */
    private function getVehicles(OptimizationState $optimizationState): array
    {
        $routeTransformer = new RouteTransformer();
        $vehicles = [];

        foreach ($optimizationState->getRoutes() as $route) {
            $vehicles[] = $routeTransformer->transform($route);
        }

        return $vehicles;
    }

    /**
     * Translates Route entity to ShipmentModel
     *
     * @param Route $route
     *
     * @return ShipmentModel
     */
    public function translateSingleRoute(Route $route): ShipmentModel
    {
        $startTime = $this->dateTimeTransformer->transform($route->getDate()->clone()->startOfDay());
        $endTime = $this->dateTimeTransformer->transform($route->getDate()->clone()->endOfDay());

        return (new ShipmentModel())
            ->setShipments($this->getRouteShipments($route)->toArray())
            ->setVehicles([$this->getVehicle($route)])
            ->setGlobalStartTime($startTime)
            ->setGlobalEndTime($endTime);
    }

    /**
     * @param Route $route
     *
     * @return Collection<Shipment>
     */
    private function getRouteShipments(Route $route): Collection
    {
        return $route->getAppointments()->map(
            fn (Appointment $appointment) => $this->appointmentTransformer->transform($appointment)
        );
    }

    private function getVehicle(Route $route): Vehicle
    {
        return $this->routeTransformer->transform($route);
    }
}
