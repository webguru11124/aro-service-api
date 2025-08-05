<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationRules;

class DetectRescheduledConfirmedAppointments implements PostOptimizationRule
{
    /**
     * @return string
     */
    public function id(): string
    {
        return 'DetectRescheduledConfirmedAppointments';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Detect Rescheduled Confirmed Appointments';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule detects if confirmed appointments are on rescheduled routes.';
    }
}
