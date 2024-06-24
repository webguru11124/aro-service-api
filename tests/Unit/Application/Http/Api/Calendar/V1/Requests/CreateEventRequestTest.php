<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\CreateEventRequest;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class CreateEventRequestTest extends AbstractRequestTest
{
    private const VALID_DATA = [
        'office_id' => 82,
        'title' => 'Test',
        'event_type' => EventType::MEETING->value,
        'start_date' => '2024-01-28',
        'start_at' => '08:00:00',
        'end_at' => '09:00:00',
        'interval' => ScheduleInterval::DAILY->value,
        'end_after' => EndAfter::NEVER->value,
    ];

    public function getTestedRequest(): Request
    {
        return new CreateEventRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'office_id_must_be_an_integer' => [
                array_merge(self::VALID_DATA, [
                    'office_id' => 'a',
                ]),
            ],
            'office_id_must_greater_than_0' => [
                array_merge(self::VALID_DATA, [
                    'office_id' => 0,
                ]),
            ],
            'title_is_mandatory' => [
                array_merge(self::VALID_DATA, [
                    'title' => '',
                ]),
            ],
            'event_type_must_be_valid' => [
                array_merge(self::VALID_DATA, [
                    'event_type' => 'random',
                ]),
            ],
            'start_date_must_have_date_format' => [
                array_merge(self::VALID_DATA, [
                    'start_date' => 'non date', // invalid format
                ]),
            ],
            'end_date_must_have_date_format' => [
                array_merge(self::VALID_DATA, [
                    'end_date' => 'non date', // invalid format
                ]),
            ],
            'start_at_must_have_time_format' => [
                array_merge(self::VALID_DATA, [
                    'start_at' => 'a b c',
                ]),
            ],
            'end_at_must_have_time_format' => [
                array_merge(self::VALID_DATA, [
                    'end_at' => 'a b c',
                ]),
            ],
            'week_days_required_and_must_be_day_of_week' => [
                array_merge(self::VALID_DATA, [
                    'interval' => ScheduleInterval::WEEKLY->value,
                    'week_days' => ['test'],
                ]),
            ],
            'location_lat_must_be_numeric' => [
                array_merge(self::VALID_DATA, [
                    'location_lat' => 'ab',
                    'location_lng' => 2.67676,
                ]),
            ],
            'location_lng_must_be_numeric' => [
                array_merge(self::VALID_DATA, [
                    'location_lat' => 1.34545,
                    'location_lng' => 'ab',
                ]),
            ],
            'state_must_be_two_letters_long' => [
                array_merge(self::VALID_DATA, [
                    'state' => 'ZZZ',
                ]),
            ],
            'zip_must_be_five_letters_long' => [
                array_merge(self::VALID_DATA, [
                    'zip' => '123456',
                ]),
            ],
            'title_max_length_exceeded' => [
                array_merge(self::VALID_DATA, [
                    'title' => str_repeat('a', 101),
                ]),
            ],
            'meeting_link_max_length_exceeded' => [
                array_merge(self::VALID_DATA, [
                    'meeting_link' => 'https://' . str_repeat('a', 191),
                ]),
            ],
            'description_max_length_exceeded' => [
                array_merge(self::VALID_DATA, [
                    'description' => str_repeat('a', 501),
                ]),
            ],
            'start_at_must_not_be_greater_than_end_at' => [
                array_merge(self::VALID_DATA, [
                    'start_at' => '10:00:00',
                    'end_at' => '09:00:00',
                ]),
            ],
            'end_after_is_required' => [
                array_merge(self::VALID_DATA, [
                    'end_after' => null,
                ]),
            ],
            'end_date_required_if_end_after_date' => [
                [
                    'office_id' => 1,
                    'title' => 'Test',
                    'event_type' => EventType::MEETING->value,
                    'start_date' => '2024-01-28',
                    'start_at' => '10:00:00',
                    'end_at' => '09:00:00',
                    'interval' => ScheduleInterval::DAILY->value,
                    'end_after' => EndAfter::DATE->value,
                    'occurrences' => 5,
                ],
            ],
            'occurrences_required_if_end_after_occurrences' => [
                [
                    'office_id' => 1,
                    'title' => 'Test',
                    'event_type' => EventType::MEETING->value,
                    'start_date' => '2024-01-28',
                    'start_at' => '10:00:00',
                    'end_at' => '09:00:00',
                    'interval' => ScheduleInterval::DAILY->value,
                    'end_after' => EndAfter::OCCURRENCES->value,
                    'end_date' => '2024-04-28',
                ],
            ],
            'occurrences_must_be_greater_than_0' => [
                array_merge(self::VALID_DATA, [
                    'end_after' => EndAfter::OCCURRENCES->value,
                    'end_date' => '2024-04-28',
                    'occurrences' => 0,
                ]),
            ],
            'week_num_must_be_in_range_if_interval_monthly' => [
                array_merge(self::VALID_DATA, [
                    'interval' => ScheduleInterval::MONTHLY->value,
                    'end_after' => EndAfter::NEVER->value,
                    'week_num' => 7,
                ]),
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data_minimum_required' => [
                self::VALID_DATA,
            ],
            'valid_request_data_all_fields' => [
                [
                    'office_id' => 82,
                    'title' => 'Test',
                    'description' => 'Some description',
                    'event_type' => EventType::MEETING->value,
                    'start_date' => '2024-01-28',
                    'end_date' => '2025-01-28',
                    'start_at' => '08:00:00',
                    'end_at' => '09:00:00',
                    'interval' => ScheduleInterval::WEEKLY->value,
                    'week_days' => [WeekDay::MONDAY->value],
                    'location_lat' => 1.34545,
                    'location_lng' => 2.67676,
                    'meeting_link' => 'meet.google.com/aaa-bbbb-ccc',
                    'address' => '123 Av.',
                    'city' => 'Los Angeles',
                    'state' => 'LA',
                    'zip' => '12345',
                    'end_after' => EndAfter::OCCURRENCES->value,
                    'occurrences' => 5,
                ],
            ],
            'valid_request_data_end_after_date' => [
                array_merge(self::VALID_DATA, [
                    'end_after' => EndAfter::DATE->value,
                ]),
            ],
            'valid_request_data_end_after_occurrences' => [
                array_merge(self::VALID_DATA, [
                    'end_after' => EndAfter::OCCURRENCES->value,
                    'occurrences' => 5,
                ]),
            ],
            'valid_request_data_with_week_number_in_range' => [
                array_merge(self::VALID_DATA, [
                    'interval' => ScheduleInterval::MONTHLY->value,
                    'end_after' => EndAfter::NEVER->value,
                    'week_num' => 1,
                ]),
            ],
            'valid_request_data_with_week_number_nullable' => [
                array_merge(self::VALID_DATA, [
                    'interval' => ScheduleInterval::MONTHLY->value,
                    'end_after' => EndAfter::NEVER->value,
                    'week_num' => null,
                ]),
            ],
        ];
    }
}
