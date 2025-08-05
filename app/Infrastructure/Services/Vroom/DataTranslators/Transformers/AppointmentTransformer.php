<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Infrastructure\Services\Vroom\DTO\Delivery;
use App\Infrastructure\Services\Vroom\DTO\Job;
use App\Infrastructure\Services\Vroom\DTO\Skills;

class AppointmentTransformer
{
    /**
     * @param Appointment $appointment
     *
     * @return Job
     */
    public function transform(Appointment $appointment): Job
    {
        $location = (new CoordinateTransformer())->transform($appointment->getLocation());

        $vroomSkills = new Skills();
        $skillsTransformer = new SkillTransformer();

        foreach ($appointment->getSkills()->all() as $skill) {
            $vroomSkills->add($skillsTransformer->transform($skill));
        }

        $delivery = new Delivery([1]);

        $timeWindow = (new TimeWindowTransformer())->transform($appointment->getExpectedArrival());

        return new Job(
            id: $appointment->getId(),
            description: $appointment->getDescription(),
            skills: $vroomSkills,
            service: $appointment->getDuration()->getTotalSeconds(),
            delivery: $delivery,
            location: $location,
            priority: $appointment->getPriority(),
            setup: $appointment->getSetupDuration()->getTotalSeconds(),
            timeWindows: $timeWindow
        );
    }
}
