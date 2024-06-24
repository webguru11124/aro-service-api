<?php

declare(strict_types=1);

return [
    'spot_duration_time' => 30, //30 min
    'lunch_duration' => 30, //30 min
    'work_break_duration' => 15, //15 min
    'first_work_break_time_window' => [90, 150], //between 1.5 to 2.5 hours (9:30 - 10:30)
    'lunch_time_window' => [180, 240], //between 3 and 4 hours (11:00 - 12:00)
    'last_work_break_time_window' => [300, 390], //between 5 and 6.5 hours (13:00 - 14:30)

    'max_working_time' => [
        'summer' => [
            'extended_routes' => 830, //13.8 hours
            'default' => 760, //12.6 hours
        ],
        'winter' => [
            'extended_routes' => 690, //11.5 hours
            'default' => 630, //10.5 hours
        ],
    ],

    'appointment_priority' => [
        'locked' => 100,
        'notified' => 100,
        'initial' => 90,
        'reservice' => 90,
        'half_day' => 90,
        'default' => 25,
    ],
    'appointment_setup_time' => [
        'initial' => 5, //5 min
        'default' => 3, //3 min
    ],
    'initial_appointment_duration' => 40, //40 min
    'regular_appointment_duration' => 20, //20 min
    'default_appointment_duration' => 15, //15 min

    'blocked_inside_sales_count' => [
        'short_routes' => 1,
        'default' => 2,
    ],

    'max_allowed_appointments_per_day' => [
        'short_routes' => 10,
        'regular_routes' => 16,
        'extended_routes' => 18,
    ],
    'reserved_spots_for_blocked_reasons' => [
        'short_routes' => 5,
        'default' => 6,
    ],
    'travel_time_to_first_location' => 10, //10 min

    'min_required_completed_appointments_to_determine_average_duration' => 3,

    'allow_route_optimization_before_time' => '07:30',

    'min_days_to_allow_reschedule_unconfirmed_appointments' => 4,
];
