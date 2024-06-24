<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use Google\Cloud\Optimization\V1\Shipment;
use Google\Cloud\Optimization\V1\Shipment\VisitRequest;

class AppointmentTransformer
{
    /**
     * @param Appointment $appointment
     *
     * @return Shipment
     */
    public function transform(Appointment $appointment): Shipment
    {
        $location = (new CoordinateTransformer())->transform($appointment->getLocation());
        $duration = (new DurationTransformer())->transform($appointment->getTotalServiceTime());
        $timeWindow = (new TimeWindowTransformer())->transform($appointment->getTimeWindow());

        $visitRequest = (new VisitRequest())
            ->setArrivalLocation($location)
            ->setLabel((string) $appointment->getId())
            ->setDuration($duration)
            ->setTimeWindows([$timeWindow]);

        return (new Shipment())->setDeliveries([$visitRequest]);
    }
}
