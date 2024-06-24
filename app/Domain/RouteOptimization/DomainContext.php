<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization;

use App\Domain\RouteOptimization\ValueObjects\RouteType;

class DomainContext
{
    private const UNKNOWN_ROUTE_TYPE_MAX_ALLOWED_APPOINTMENTS = 0;

    public static function getWorkBreakDuration(): int
    {
        return config('aptive.work_break_duration');
    }

    public static function getLunchDuration(): int
    {
        return config('aptive.lunch_duration');
    }

    public static function getMaxWorkTime(RouteType|null $routeType, bool $onSeason = false): int
    {
        $season = $onSeason ? 'summer' : 'winter';

        return match($routeType) {
            RouteType::EXTENDED_ROUTE => config("aptive.max_working_time.$season.extended_routes"),
            default => config("aptive.max_working_time.$season.default"),
        };
    }

    /**
     * @return int[]
     */
    public static function getFirstWorkBreakTimeWindow(): array
    {
        return config('aptive.first_work_break_time_window');
    }

    /**
     * @return int[]
     */
    public static function getLastWorkBreakTimeWindow(): array
    {
        return config('aptive.last_work_break_time_window');
    }

    /**
     * @return int[]
     */
    public static function getLunchTimeWindow(): array
    {
        return config('aptive.lunch_time_window');
    }

    /**
     * @param string $appointmentType
     *
     * @return int
     */
    public static function getAppointmentPriority(string $appointmentType): int
    {
        return config('aptive.appointment_priority.' . $appointmentType);
    }

    /**
     * @return string
     */
    public static function getAllowRouteOptimizationBeforeTime(): string
    {
        return config('aptive.allow_route_optimization_before_time');
    }

    /**
     * @param string $appointmentType
     *
     * @return int
     */
    public static function getAppointmentSetUpTime(string $appointmentType): int
    {
        return config('aptive.appointment_setup_time.' . $appointmentType);
    }

    /**
     * @return int
     */
    public static function getAppointmentDefaultDuration(): int
    {
        return config('aptive.default_appointment_duration');
    }

    /**
     * @return int
     */
    public static function getInitialAppointmentDuration(): int
    {
        return config('aptive.initial_appointment_duration');
    }

    /**
     * @return int
     */
    public static function getRegularAppointmentDuration(): int
    {
        return config('aptive.regular_appointment_duration');
    }

    /**
     * @param RouteType $routeType
     *
     * @return int
     */
    public static function getMaxAllowedAppointmentsPerDay(RouteType $routeType): int
    {
        return match($routeType) {
            RouteType::REGULAR_ROUTE => config('aptive.max_allowed_appointments_per_day.regular_routes'),
            RouteType::EXTENDED_ROUTE => config('aptive.max_allowed_appointments_per_day.extended_routes'),
            RouteType::SHORT_ROUTE => config('aptive.max_allowed_appointments_per_day.short_routes'),
            RouteType::UNKNOWN => self::UNKNOWN_ROUTE_TYPE_MAX_ALLOWED_APPOINTMENTS,
        };
    }

    /**
     * @param RouteType $routeType
     *
     * @return int
     */
    public static function getReservedSpotsForBlockedReasons(RouteType $routeType): int
    {
        return match($routeType) {
            RouteType::SHORT_ROUTE => config('aptive.reserved_spots_for_blocked_reasons.short_routes'),
            default => config('aptive.reserved_spots_for_blocked_reasons.default'),
        };
    }

    /**
     * @return int
     */
    public static function getBlockedInsideSalesCount(RouteType|null $routeType = null): int
    {
        return $routeType === RouteType::SHORT_ROUTE
            ? config('aptive.blocked_inside_sales_count.short_routes')
            : config('aptive.blocked_inside_sales_count.default');
    }

    /**
     * @return int
     */
    public static function getTravelTimeToFirstLocation(): int
    {
        return config('aptive.travel_time_to_first_location');
    }

    /**
     * @return int
     */
    public static function getMinAppointmentsToDetermineDuration(): int
    {
        return config('aptive.min_required_completed_appointments_to_determine_average_duration');
    }

    /**
     * @return int
     */
    public static function getSpotDuration(): int
    {
        return config('aptive.spot_duration_time');
    }

    /**
     * @return int
     */
    public static function getMinDaysToAllowRescheduleUnconfirmedAppointments(): int
    {
        return config('aptive.min_days_to_allow_reschedule_unconfirmed_appointments');
    }
}
