<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterval;

trait DomainDataAndObjects
{
    protected const APPOINTMENT_BASIC_LABEL = 'Basic';
    protected const APPOINTMENT_PRO_PLUS_LABEL = 'Pro Plus';
    protected const APPOINTMENT_QUARTERLY_SERVICE_LABEL = 'Quarterly Service';
    private const LOCATION_START_LATITUDE = 34.2343;
    private const LOCATION_START_LONGITUDE = -71.2244;
    private const LOCATION_END_LATITUDE = 34.1111;
    private const LOCATION_END_LONGITUDE = -71.1111;
    protected const START_OF_15_MINUTE_BREAK_TIMESTAMP = 1687015350;
    protected const END_OF_15_MINUTE_BREAK_TIMESTAMP = 1687015350;
    protected const START_OF_LUNCH_TIMESTAMP = 1687015350;
    protected const END_OF_LUNCH_TIMESTAMP = 1687020000;
    protected const START_OF_WORKDAY_TIMESTAMP = 1690531200;
    protected const END_OF_WORKDAY_TIMESTAMP = 1690563600;
    protected const SERVICE_PRO_ID = 1000;
    protected const SERVICE_PRO_NAME = 'John Snow';
    protected const TIMESTAMP_3_MINUTES = 180;
    protected const TIMESTAMP_15_MINUTES = 900;
    protected const TIMESTAMP_25_MINUTES = 1500;
    protected const TIMESTAMP_30_MINUTES = 1800;
    protected const WORK_BREAK_LUNCH_ID = 2;
    protected const WORK_BREAK_LUNCH_LABEL = 'Lunch';
    protected const WORK_BREAK_15_MINUTE_ID = 1;
    protected const RESERVED_BREAK_ID = 1234;
    protected const RESERVED_BREAK_LABEL = 'Not Working';
    protected const WORK_BREAK_15_MINUTE_LABEL = '15 Min Break';
    protected const DEFAULT_SPEED_FACTOR = 1.00;

    protected function domainDuration3Minutes(): Duration
    {
        return new Duration(CarbonInterval::seconds(self::TIMESTAMP_3_MINUTES));
    }

    protected function domainDuration15Minutes(): Duration
    {
        return new Duration(CarbonInterval::seconds(self::TIMESTAMP_15_MINUTES));
    }

    protected function domainDuration30Minutes(): Duration
    {
        return new Duration(CarbonInterval::seconds(self::TIMESTAMP_30_MINUTES));
    }

    protected function domainDuration25Minutes(): Duration
    {
        return new Duration(CarbonInterval::seconds(self::TIMESTAMP_25_MINUTES));
    }

    protected function domain15MinuteBreak(): WorkBreak
    {
        return (new WorkBreak(
            self::WORK_BREAK_15_MINUTE_ID,
            self::WORK_BREAK_15_MINUTE_LABEL,
        ))
            ->setDuration($this->domainDuration15Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(self::START_OF_15_MINUTE_BREAK_TIMESTAMP),
                Carbon::createFromTimestamp(self::END_OF_15_MINUTE_BREAK_TIMESTAMP),
            ));
    }

    protected function domainLunch(): Lunch
    {
        return (new Lunch(
            self::WORK_BREAK_LUNCH_ID,
            self::WORK_BREAK_LUNCH_LABEL,
        ))
            ->setDuration($this->domainDuration30Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(self::START_OF_LUNCH_TIMESTAMP),
                Carbon::createFromTimestamp(self::END_OF_LUNCH_TIMESTAMP),
            ));
    }
}
