<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\RouteOptimization\ValueObjects\Skill;
use Illuminate\Support\Carbon;

trait VroomDataAndObjects
{
    use DomainDataAndObjects;

    protected const START_OF_WORKDAY_DATETIME = '2023-07-28 08:00:00';
    protected const END_OF_WORKDAY_DATETIME = '2023-07-28 17:00:00';

    protected function vroomLast15MinuteBreak(): array
    {
        // Starting possible last break time is 6 hours into the working day
        $breakTime = Carbon::parse(self::START_OF_WORKDAY_DATETIME)->copy()->addHours(6);

        // 6 hours after start of day and every 15 min interval for the next 1 hour are possible times for the last break
        // Thus there are 4 possible 15 min break intervals in one hour (Between 2 PM and 3 PM)
        $possibleTimesForLastBreak = [
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(15)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(15)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(15)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(15)->timestamp,
            ],
        ];

        return [
            'description' => self::WORK_BREAK_15_MINUTE_LABEL,
            'id' => self::WORK_BREAK_15_MINUTE_ID,
            'service' => self::TIMESTAMP_15_MINUTES,
            'time_windows' => $possibleTimesForLastBreak,
        ];
    }

    protected function vroomFirst15MinuteBreak(): array
    {
        // Starting possible break time is one hour after the start of the working day
        $breakTime = Carbon::parse(self::START_OF_WORKDAY_DATETIME)->copy()->addHours(1);

        // 1 hour after start of day and every 15 min interval for the next 1 hour are possible times for the first break
        // Thus there are 4 possible 15 min break intervals in one hour (Between 9 AM and 10 AM)
        $possibleTimesForFirstBreak = [
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(15)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(15)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(15)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(15)->timestamp,
            ],
        ];

        return [
            'description' => self::WORK_BREAK_15_MINUTE_LABEL,
            'id' => self::WORK_BREAK_15_MINUTE_ID,
            'service' => self::TIMESTAMP_15_MINUTES,
            'time_windows' => $possibleTimesForFirstBreak,
        ];
    }

    protected function vroomLunch(): array
    {
        // Starting possible lunch break time is 3 hours into the working day
        $breakTime = Carbon::parse(self::START_OF_WORKDAY_DATETIME)->copy()->addHours(3);

        // 3 hours after start of day and every 30 min interval for the next 2 hour are possible times for the last break
        // Thus there are 4 possible 30 min break intervals in one hour (Between 11 AM and 1 PM)
        $possibleTimesForLunchBreak = [
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(30)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(30)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(30)->timestamp,
            ],
            [
                $breakTime->timestamp,
                $breakTime->addMinutes(30)->timestamp,
            ],
        ];

        return [
            'description' => self::WORK_BREAK_LUNCH_LABEL,
            'id' => self::WORK_BREAK_LUNCH_ID,
            'service' => self::TIMESTAMP_30_MINUTES,
            'time_windows' => $possibleTimesForLunchBreak,
        ];
    }

    protected function vroomServicePro(): array
    {
        return [
            'description' => self::SERVICE_PRO_NAME,
            'end' => [
                self::LOCATION_END_LONGITUDE,
                self::LOCATION_END_LATITUDE,
            ],
            'skills' => [
                Skill::INITIAL_SERVICE,
                Skill::TX,
            ],
            'start' => [
                self::LOCATION_START_LONGITUDE,
                self::LOCATION_START_LATITUDE,
            ],
            'time_window' => [],
            'capacity' => [],
            'id' => self::SERVICE_PRO_ID,
            'breaks' => [],
            'speed_factor' => self::DEFAULT_SPEED_FACTOR,
        ];
    }
}
