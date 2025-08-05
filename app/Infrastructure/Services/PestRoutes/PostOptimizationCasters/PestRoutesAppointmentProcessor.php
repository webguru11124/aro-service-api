<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Illuminate\Support\Collection;

trait PestRoutesAppointmentProcessor
{
    public function __construct(
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
    ) {
    }

    /**
     * @param int $officeId
     * @param Collection<Route> $routes
     *
     * @return Collection<PestRoutesAppointment>
     */
    private function getAllAppointments(int $officeId, Collection $routes, callable $filterCallback = null): Collection
    {
        $allAppointments = $this->appointmentsDataProcessor->extract($officeId, new SearchAppointmentsParams(
            officeIds: [$officeId],
            status: AppointmentStatus::Pending,
            routeIds: $routes->map(fn ($route) => $route->getId())->toArray(),
        ));

        if (!empty($filterCallback)) {
            $allAppointments = $allAppointments->filter($filterCallback);
        }

        return $allAppointments;
    }

    private function getAppointmentById(Route $route, int $id): Appointment|null
    {
        return $route->getAppointments()->first(fn (Appointment $appointment) => $appointment->getId() === $id);
    }
}
