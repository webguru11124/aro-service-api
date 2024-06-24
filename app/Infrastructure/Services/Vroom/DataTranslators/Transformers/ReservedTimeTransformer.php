<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Infrastructure\Services\Vroom\DTO\VroomBreak;

class ReservedTimeTransformer
{
    /**
     * Transforms Reserved Break to VroomBreak
     *
     * @param ReservedTime $reservedTime
     *
     * @return VroomBreak
     */
    public function transform(ReservedTime $reservedTime): VroomBreak
    {
        $timeWindowTransformer = new TimeWindowTransformer();

        return new VroomBreak(
            $reservedTime->getId(),
            $reservedTime->getDescription(),
            $reservedTime->getDuration()->getTotalSeconds(),
            null,
            $timeWindowTransformer->transform($reservedTime->getExpectedArrival()),
        );
    }
}
