<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\BusinessRuleCasters;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Infrastructure\Services\Vroom\DTO\Job;
use App\Infrastructure\Services\Vroom\DTO\Vehicle;

abstract class AbstractBusinessRuleCaster implements BusinessRuleCaster
{
    /**
     * @param OptimizationState $optimizationState
     * @param Vehicle $vehicle
     *
     * @return ServicePro|null
     */
    protected function findServiceProByVehicle(OptimizationState $optimizationState, Vehicle $vehicle): ServicePro|null
    {
        return $optimizationState
            ->getRoutes()
            ->filter(fn (Route $route) => $route->getId() === $vehicle->getId())
            ->map(fn (Route $route) => $route->getServicePro())
            ->filter()
            ->first();
    }

    /**
     * @param OptimizationState $optimizationState
     * @param Job $job
     *
     * @return Appointment|null
     */
    protected function findAppointmentByJob(OptimizationState $optimizationState, Job $job): Appointment|null
    {
        $appointments = [];
        foreach ($optimizationState->getRoutes() as $route) {
            $appointments = array_merge($appointments, $route->getAppointments()->all());
        }

        foreach ($appointments as $appointment) {
            if ($appointment->getId() === $job->getId()) {
                return $appointment;
            }
        }

        return null;
    }
}
