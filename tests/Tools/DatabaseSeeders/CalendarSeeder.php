<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalendarSeeder extends Seeder
{
    public const DATA_EVENTS = [
        'id' => [1000000, 1000001],
        'title' => ['Regular title 1', 'Regular title 2'],
        'description' => ['Regular description 1', 'Regular description 2'],
        'event_type' => ['meeting', 'office-event'],
        'office_id' => [999, 999],
        'start_date' => ['2024-01-01', '2024-05-01'],
        'end_date' => ['2024-04-30', '2024-09-30'],
        'start_time' => ['08:00:00', '08:30:00'],
        'end_time' => ['08:30:00', '08:50:00'],
        'time_zone' => ['EST', 'EST'],
        'location' => ['{"lat": 30.1234, "lon": -70.1234}', '{"lat": 30.1234, "lon": -70.1234}'],
        'interval' => ['weekly', 'weekly'],
        'week_days' => ['monday', 'tuesday'],
        'repeat_every' => [1, 1],
    ];

    public const DATA_OVERRIDES = [
        'id' => [1, 2],
        'schedule_id' => [1000001, 1000001],
        'title' => ['Updated title', 'Canceled title'],
        'description' => ['Updated description', 'Canceled description'],
        'is_canceled' => [false, true],
        'start_time' => ['08:10:00', '08:30:00'],
        'end_time' => ['08:40:00', '08:50:00'],
        'time_zone' => ['EST', 'EST'],
        'location' => ['{"lat": 30.1234, "lon": -70.1234}', '{"lat": 30.1234, "lon": -70.1234}'],
        'date' => ['2024-06-26', '2024-07-24'],
    ];

    public const DATA_PARTICIPANTS = [
        'schedule_id' => [1000000, 1000000, 1000001, 1000001, 1000001],
        'employee_id' => [111, 222, 111, 222, 333],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < count(self::DATA_EVENTS['id']); $i++) {
            DB::table('field_operations.office_days_schedule')->insert([
                'id' => self::DATA_EVENTS['id'][$i],
                'title' => self::DATA_EVENTS['title'][$i],
                'description' => self::DATA_EVENTS['description'][$i],
                'office_id' => self::DATA_EVENTS['office_id'][$i],
                'event_type' => self::DATA_EVENTS['event_type'][$i],
                'start_date' => self::DATA_EVENTS['start_date'][$i],
                'end_date' => self::DATA_EVENTS['end_date'][$i],
                'start_time' => self::DATA_EVENTS['start_time'][$i],
                'end_time' => self::DATA_EVENTS['end_time'][$i],
                'time_zone' => self::DATA_EVENTS['time_zone'][$i],
                'location' => self::DATA_EVENTS['location'][$i],
                'interval' => self::DATA_EVENTS['interval'][$i],
                'occurrence' => self::DATA_EVENTS['week_days'][$i],
                'repeat_every' => self::DATA_EVENTS['repeat_every'][$i],
            ]);
        }

        for ($i = 0; $i < count(self::DATA_OVERRIDES['id']); $i++) {
            DB::table('field_operations.office_days_schedule_overrides')->insert([
                'id' => self::DATA_OVERRIDES['id'][$i],
                'schedule_id' => self::DATA_OVERRIDES['schedule_id'][$i],
                'title' => self::DATA_OVERRIDES['title'][$i],
                'description' => self::DATA_OVERRIDES['description'][$i],
                'is_canceled' => self::DATA_OVERRIDES['is_canceled'][$i],
                'start_time' => self::DATA_OVERRIDES['start_time'][$i],
                'end_time' => self::DATA_OVERRIDES['end_time'][$i],
                'time_zone' => self::DATA_OVERRIDES['time_zone'][$i],
                'location' => self::DATA_OVERRIDES['location'][$i],
                'date' => self::DATA_OVERRIDES['date'][$i],
            ]);
        }

        for ($i = 0; $i < count(self::DATA_PARTICIPANTS['schedule_id']); $i++) {
            DB::table('field_operations.office_days_participants')->insert([
                'schedule_id' => self::DATA_PARTICIPANTS['schedule_id'][$i],
                'employee_id' => self::DATA_PARTICIPANTS['employee_id'][$i],
            ]);
        }
    }
}
