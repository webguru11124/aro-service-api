<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Services;

use App\Domain\RouteOptimization\Services\BusinessDaysService;
use Carbon\Carbon;
use Tests\TestCase;

class BusinessDaysServiceTest extends TestCase
{
    public const CUT_OFF_HOUR = 13;
    public const TIMEZONE = 'MST';

    private BusinessDaysService $businessDays;

    protected function setUp(): void
    {
        $this->businessDays = new BusinessDaysService();

        parent::setUp();
    }

    /**
     * @test
     *
     * @dataProvider getTestConditions
     */
    public function needs_first_appointment_blocked_test(Carbon $now, Carbon $optimizationDate, bool $expectation): void
    {
        Carbon::setTestNow($now);

        $result = $this->businessDays->needsFirstAppointmentLock($optimizationDate);

        $this->assertEquals($expectation, $result);
    }

    public static function getTestConditions(): array
    {
        $fridayAfterCutoff = (new Carbon('2023-08-11T13:00:00', self::TIMEZONE));
        $thursdayAfterCutoff = (new Carbon('2023-08-10T13:00:00', self::TIMEZONE));
        $saturday = (new Carbon('2023-08-12T00:00:00', self::TIMEZONE));
        $monday = (new Carbon('2023-08-14T00:00:00', self::TIMEZONE));

        return [
            'before_cut_off_time_is_never_blocked' => [
                Carbon::now(self::TIMEZONE)->setTimeFromTimeString(self::CUT_OFF_HOUR - 5),
                Carbon::tomorrow(self::TIMEZONE),
                false,
            ],
            'if_its_friday_lock_saturday' => [
                $fridayAfterCutoff,
                $saturday,
                true,
            ],
            'if_its_friday_lock_monday' => [
                $fridayAfterCutoff,
                $monday,
                true,
            ],
            'if_its_new_years_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2024-01-01'),
                false,
            ],
            'if_its_the_day_after_new_years_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2025-01-02'),
                false,
            ],
            'if_its_memorial_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2027-05-31'),
                false,
            ],
            'if_its_independence_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2024-07-04'),
                false,
            ],
            'if_its_labor_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2026-09-07'),
                false,
            ],
            'if_its_thanksgiving_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2025-11-27'),
                false,
            ],
            'if_its_the_day_after_thanksgiving_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2024-11-29'),
                false,
            ],
            'if_its_the_day_before_christmas_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2024-12-24'),
                false,
            ],
            'if_its_christmas_day_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2025-12-25'),
                false,
            ],
            'if_its_the_day_of_the_year_no_locking' => [
                $fridayAfterCutoff,
                new Carbon('2026-12-31'),
                false,
            ],
            'if_its_thursday_lock_friday' => [
                $thursdayAfterCutoff,
                new Carbon('2023-08-11'),
                true,
            ],
            'if_its_thursday_do_not_lock_monday' => [
                $thursdayAfterCutoff,
                new Carbon('2023-08-14'),
                false,
            ],
        ];
    }
}
