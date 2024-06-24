<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Google\Cloud\Optimization\V1\TimeWindow as GoogleTimeWindow;

class TimeWindowTransformer
{
    /**
     * @param TimeWindow $timeWindow
     *
     * @return GoogleTimeWindow
     */
    public function transform(TimeWindow $timeWindow): GoogleTimeWindow
    {
        $carbonTransformer = new DateTimeTransformer();
        $startTimeStamp = $carbonTransformer->transform($timeWindow->getStartAt());
        $endTimeStamp = $carbonTransformer->transform($timeWindow->getEndAt());

        // todo: would setSoftStartTime() be better?
        return (new GoogleTimeWindow())
            ->setStartTime($startTimeStamp)
            ->setEndTime($endTimeStamp);
    }
}
