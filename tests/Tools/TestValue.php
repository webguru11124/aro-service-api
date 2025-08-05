<?php

declare(strict_types=1);

namespace Tests\Tools;

class TestValue
{
    public const APPOINTMENT_DURATION = 30;
    public const APPOINTMENT_SETUP_DURATION = 3;
    public const START_OF_DAY = 8;
    public const END_OF_DAY = 20;
    public const APPOINTMENT_ID = 23434;
    public const CUSTOMER_TIME_ZONE = 'PST';
    public const DATE_FORMAT = 'Y-m-d';
    public const LATITUDE = 40.30280;
    public const LONGITUDE = -111.661919;
    public const MIN_LATITUDE = 40.080295;
    public const MAX_LATITUDE = 41.274080;
    public const MIN_LONGITUDE = -112.287209;
    public const MAX_LONGITUDE = -111.514792;
    public const OFFICE_NAME = 'Demo Salt Lake City Central';
    public const OFFICE_ID = 94;
    public const ROUTE_ID = 4497004;
    public const ROUTE_START_TIME = '08:00:00';
    public const ROUTE_END_TIME = '08:00:00';

    public const OFFICE_REGIONS = [
        'Northwest',
        'Southwest',
        'Central',
        'Gulf Coast',
        'Great Lakes',
        'Midwest',
        'Mid-Atlantic',
        'Southeast',
        'Northeast',
    ];

    public const REGION_ID = 1;
    public const REGION_NAME = 'Central';

    public const SPOT_ID = 432152;
    public const TIME_FORMAT = 'H:i:s';
    public const TIME_ZONE = 'MST';
    public const TZ = 'America/Denver';
    public const WORK_BREAK_ID = 1;
    public const WORK_BREAK_DURATION = 15;
    public const EVENT_ID = 34564;
    public const OPTIMIZATION_STATE_ID = 8675309;
    public const PARTICIPANT_ID = '1234';
    public const EMPLOYEE1_ID = 518587;
    public const EMPLOYEE2_ID = 518588;
    public const USA_STATE = 'TX';
    public const WORKDAY_ACCESS_TOKEN = 'validToken';
    public const WORKDAY_VALID_CLIENT_KEY = 'valid_client_key';
    public const WORKDAY_VALID_ISU_USERNAME = 'valid_isu_username';
    public const WORKDAY_VALID_PRIVATE_KEY = 'valid_private_key';
    public const WORKDAY_VALID_HUMAN_RESOURCES_URL = 'valid_test-url';
    public const WORKDAY_ID = 'AP004277';
    public const CUSTOMER_ID = 25245435;
    public const SERVICE_TYPE_ID = 1800;
    public const SUBSCRIPTION_ID = 3255980;
    public const IVR_SCHEDULER_ID = 6537265;
    public const APPOINTMENT_REMINDER_ID = 99268247;
    public const EMPLOYEE_ID = 12345;
    public const BASIC_ID = 4;
    public const PRO_ID = 1;
    public const PRO_PLUS_ID = 2;
    public const PREMIUM_ID = 3;

    public const BASIC = 'Basic';
    public const PREMIUM = 'Premium';
    public const PRO = 'Pro';
    public const PRO_PLUS = 'Pro +';
    public const BASIC_PEST_ROUTES_ID = 1799;
    public const PRO_PEST_ROUTES_ID = 2827;
    public const PRO_PLUS_PEST_ROUTES_ID = 1800;
    public const PREMIUM_PEST_ROUTES_ID = 2828;
    public const DEFAULT_PREFERRED_START = '08:00:00';
    public const DEFAULT_PREFERRED_END = '20:00:00';
    public const INITIAL_FOLLOW_UP_INTERVAL_DAYS = 30;
    public const PLAN_DATA = [
        'id' => self::BASIC_ID,
        'name' => self::BASIC,
        'serviceTypeId' => self::BASIC_PEST_ROUTES_ID,
        'summerServiceIntervalDays' => 30,
        'winterServiceIntervalDays' => 50,
        'summerServicePeriodDays' => 30,
        'winterServicePeriodDays' => 30,
        'initialFollowUpDays' => self::INITIAL_FOLLOW_UP_INTERVAL_DAYS,
    ];
    public const GEOMETRY = 'SGLKSGHG67SHGHSLKFG78SFG8HFFGSDG7H8DGHDKG6D76GH';
    public const CUSTOMER_PROPERTY_DETAILS_ID = 1456;
    public const CUSTOMER_PHONE = '801-555-5555';

    public const TOTAL_DRIVE_TIME = 120;
    public const TOTAL_DRIVE_MILES = 2.0;
    public const OPTIMIZATION_SCORE = 0.85;
    public const TOTAL_WORKING_HOURS = 8.5;
    public const TOTAL_WEIGHTED_SERVICES = 50;
    public const AVERAGE_TIME_BETWEEN_SERVICES = 25;
    public const AVERAGE_MILES_BETWEEN_SERVICES = 1.5;
    public const AVERAGE_WEIGHTED_SERVICES_PER_HOUR = 6.0;

    public const SCHEDULING_STATE_ID = 1243;
    public const TREATMENT_STATE_ID = 7445;
    public const EVENT_DESCRIPTION = 'test description';

    public const VALID_OFFICES_DATA_FILE = 'tests/resources/Unit/Infrastructure/Repositories/Static/offices_valid_data.json';
    public const RECIPIENT_PHONES = ['8015555555', '8015555556'];
}
