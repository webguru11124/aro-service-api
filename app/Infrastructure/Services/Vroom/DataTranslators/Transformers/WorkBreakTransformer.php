<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Infrastructure\Services\Vroom\DTO\VroomBreak;

class WorkBreakTransformer
{
    /**
     * Transforms WorkBreak to VroomBreak
     *
     * @param WorkBreak $workBreak
     * @param int $appointmentsNumber
     *
     * @return VroomBreak
     */
    public function transform(WorkBreak $workBreak, int $appointmentsNumber): VroomBreak
    {
        $timeWindowTransformer = new TimeWindowTransformer();
        $maxLoad = $workBreak->getMinAppointmentsBefore() === null
            ? null
            : $appointmentsNumber - $workBreak->getMinAppointmentsBefore();

        return new VroomBreak(
            $workBreak->getId(),
            $workBreak->getDescription(),
            $workBreak->getDuration()->getTotalSeconds(),
            $maxLoad,
            $timeWindowTransformer->transform($workBreak->getExpectedArrival())
        );
    }
}
