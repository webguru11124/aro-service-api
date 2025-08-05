<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RouteSummary;

class MustUpdateRouteSummary implements PostOptimizationRule
{
    private const SUMMARY_TITLE = 'ARO Summary:';
    private const SUMMARY_PATTERN_DRIVING = 'Driving: %s.';
    private const SUMMARY_PATTERN_SERVICING = 'Servicing: %s.';
    private const SUMMARY_PATTERN_WORKING = 'Working: %s.';
    private const SUMMARY_PATTERN_EXCLUDE_APPT = 'Exclude first Appt: %s.';
    private const SUMMARY_PATTERN_AS_OF = 'As of: %s.';
    private const AS_OF_FORMAT = 'M d, h:iA T';

    /**
     * @param RouteSummary $routeSummary
     *
     * @return string
     */
    public function formatRouteSummary(RouteSummary $routeSummary): string
    {
        $summary = self::SUMMARY_TITLE;

        if ($routeSummary->drivingTime !== null) {
            $summary .= ' ' . sprintf(
                self::SUMMARY_PATTERN_DRIVING,
                $routeSummary->drivingTime->format()
            );
        }

        if ($routeSummary->servicingTime !== null) {
            $summary .= ' ' . sprintf(
                self::SUMMARY_PATTERN_SERVICING,
                $routeSummary->servicingTime->format()
            );
        }

        if ($routeSummary->totalWorkingTime !== null) {
            $summary .= ' ' . sprintf(
                self::SUMMARY_PATTERN_WORKING,
                $routeSummary->totalWorkingTime->format()
            );
        }

        $excludeFirstAppt = ucfirst(var_export($routeSummary->excludeFirstAppointment, true));
        $summary .= ' ' . sprintf(self::SUMMARY_PATTERN_EXCLUDE_APPT, $excludeFirstAppt);

        $summary .= ' ' . sprintf(self::SUMMARY_PATTERN_AS_OF, $routeSummary->asOf->format(self::AS_OF_FORMAT));

        return $summary;
    }

    /**
     * @return callable
     */
    public function getRoutesFilter(): callable
    {
        return function (Route $route) {
            return $route->getServicePro()->getSkillsWithoutPersonal()->isNotEmpty();
        };
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return 'MustUpdateRouteSummary';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Have Summary';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule adds route summary to the last spot on a route.';
    }
}
