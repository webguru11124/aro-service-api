<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\Tools\TestValue;

class OptimizationStateFactory extends AbstractFactory
{
    /**
     * @param array[
     *  'id' => int,
     *  'officeId' => int,
     *  'engine' => OptimizationEngine,
     *  'status' => OptimizationStatus,
     *  'timeFrame' => TimeWindow,
     *  'createdAt' => CarbonInterface,
     *  'routes' => array<Route>,
     *  'unassignedAppointments' => array<Appointment>,
     *  'totalDriveTime' => Duration
     * ]
     */
    public function single($overrides = []): OptimizationState
    {
        $timeFrame = $overrides['timeFrame'] ?? new TimeWindow(
            Carbon::tomorrow()->setHour(TestValue::START_OF_DAY),
            Carbon::tomorrow()->setHour(TestValue::END_OF_DAY),
        );

        $officeId = $overrides['officeId'] ?? $this->faker->randomNumber(6);
        $office = OfficeFactory::make(['id' => $officeId]);

        $optimizationState = new OptimizationState(
            $overrides['id'] ?? $this->faker->randomNumber(6),
            $overrides['engine'] ?? OptimizationEngine::VROOM,
            $overrides['status'] ?? OptimizationStatus::PRE,
            $overrides['createdAt'] ?? Carbon::now(),
            $overrides['office'] ?? $office,
            $timeFrame,
            $overrides['optimizationParams'] ?? new OptimizationParams(),
        );

        $routes = $overrides['routes'] ?? RouteFactory::many(5, ['officeId' => $officeId]);
        foreach ($routes as $route) {
            $optimizationState->addRoute($route);
        }
        $unassignedAppointments = $overrides['unassignedAppointments'] ?? AppointmentFactory::many(3);
        foreach ($unassignedAppointments as $unassignedAppointment) {
            $optimizationState->addUnassignedAppointment($unassignedAppointment);
        }

        if (isset($overrides['previousStateId'])) {
            $optimizationState->setPreviousStateId($overrides['previousStateId']);
        }

        if (isset($overrides['weatherInfo'])) {
            $optimizationState->setWeatherInfo($overrides['weatherInfo']);
        }

        return $optimizationState;
    }
}
