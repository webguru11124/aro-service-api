<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres\Scopes;

class PostgresDBInfo
{
    public const SCHEMA = 'field_operations';
    public const OPTIMIZATION_STATE_TABLE = self::SCHEMA . '.optimization_states';
    public const ROUTE_STATS_TABLE = self::SCHEMA . '.route_stats';
    public const OFFICE_DAYS_SCHEDULE = self::SCHEMA . '.office_days_schedule';
    public const OFFICE_DAYS_SCHEDULE_OVERRIDES = self::SCHEMA . '.office_days_schedule_overrides';
    public const OFFICE_DAYS_PARTICIPANTS = self::SCHEMA . '.office_days_participants';
    public const ROUTE_GEOMETRY_TABLE = self::SCHEMA . '.route_geometries';
    public const NOTIFICATION_RECIPIENTS_TABLE = self::SCHEMA . '.notification_recipients';
    public const NOTIFICATION_TYPES_TABLE = self::SCHEMA . '.notification_types';
    public const NOTIFICATION_RECIPIENT_TYPE_TABLE = self::SCHEMA . '.notification_recipient_type';
    public const SCHEDULING_STATES_TABLE = self::SCHEMA . '.scheduling_states';
    public const SCHEDULED_ROUTE_DETAILS = self::SCHEMA . '.scheduled_route_details';
    public const CUSTOMER_PROPERTY_DETAILS_TABLE = self::SCHEMA . '.customer_property_details';
    public const MONTHLY_FINANCIAL_REPORTS_TABLE = self::SCHEMA . '.monthly_financial_reports';
    public const ROUTE_DETAILS_TABLE = self::SCHEMA . '.route_details';
    public const TREATMENT_STATE_TABLE = self::SCHEMA . '.treatment_states';
    public const SERVICED_ROUTE_DETAILS_TABLE = self::SCHEMA . '.serviced_route_details';
}
