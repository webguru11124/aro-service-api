<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\Vroom\DTO\VroomTimeWindow;

class TimeWindowTransformer
{
    /**
     * @param TimeWindow $timeWindow
     *
     * @return VroomTimeWindow
     */
    public function transform(TimeWindow $timeWindow): VroomTimeWindow
    {
        return new VroomTimeWindow(
            $timeWindow->getStartAt(),
            $timeWindow->getEndAt()
        );
    }
}
