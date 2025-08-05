<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators\Hydrators;

use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

/**
 * @phpstan-type VroomResponseWorkBreak array{
 *     arrival:int,
 *     description:string,
 *     duration:int,
 *     id:int,
 *     service:int,
 * }
 */
class WorkBreakHydrator
{
    private const LUNCH_MARKER = 'lunch';

    /**
     * @param VroomResponseWorkBreak $data
     * @param CarbonTimeZone $timeZone
     *
     * @return WorkBreak
     */
    public function hydrate(array $data, CarbonTimeZone $timeZone): WorkBreak
    {
        if (stripos($data['description'], self::LUNCH_MARKER) !== false) {
            $workBreak = new Lunch(
                $data['id'],
                $data['description'],
            );
        } else {
            $workBreak = new WorkBreak(
                $data['id'],
                $data['description'],
            );
        }
        $serviceDuration = Duration::fromSeconds($data['service']);
        $workBreak
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp($data['arrival'], $timeZone),
                Carbon::createFromTimestamp($data['arrival'] + $data['service'], $timeZone),
            ))
            ->setDuration($serviceDuration);

        return $workBreak;
    }
}
