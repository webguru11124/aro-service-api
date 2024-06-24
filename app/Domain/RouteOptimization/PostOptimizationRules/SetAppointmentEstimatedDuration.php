<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;

class SetAppointmentEstimatedDuration implements PostOptimizationRule
{
    private const ROUNDING_INTERVAL = 5;

    /**
     * @param Appointment $appointment
     *
     * @return string
     */
    public function generateDurationNotes(Appointment $appointment, string $existingNotes): string
    {
        $minMinutes = $appointment->getMinimumDuration()?->getTotalMinutes() ?? 'N/A';
        $maxMinutes = $appointment->getMaximumDuration()?->getTotalMinutes() ?? 'N/A';
        $newDurationNotes = sprintf(
            "Minimum Duration: %s\nMaximum Duration: %s\nOptimal Duration: %s\n",
            $minMinutes,
            $maxMinutes,
            $appointment->getDuration()->getTotalMinutes(),
        );

        $pattern = '/Minimum Duration: .*?\\nMaximum Duration: .*?\\nOptimal Duration: .*?\\n/s';

        if (preg_match($pattern, $existingNotes)) {
            $newNotes = preg_replace($pattern, $newDurationNotes, $existingNotes);
        } else {
            $newNotes = $existingNotes . PHP_EOL . $newDurationNotes;
        }

        return $newNotes;
    }

    /**
     * @param int $duration
     *
     * @return int
     */
    public function roundDuration(int $duration): int
    {
        return (int) round($duration / self::ROUNDING_INTERVAL) * self::ROUNDING_INTERVAL;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return 'SetAppointmentEstimatedDuration';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Set Appointment Estimated Duration';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rules sets appointment estimated duration and adds min and max duration to notes';
    }
}
