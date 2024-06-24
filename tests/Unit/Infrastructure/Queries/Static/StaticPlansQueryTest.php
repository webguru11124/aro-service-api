<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\Static;

use App\Domain\Scheduling\Entities\Plan;
use App\Infrastructure\Queries\Static\StaticPlansQuery;
use Tests\TestCase;
use Tests\Tools\TestValue;

class StaticPlansQueryTest extends TestCase
{
    private const PLANS = [
        TestValue::BASIC_ID => [
            'id' => TestValue::BASIC_ID,
            'name' => TestValue::BASIC,
            'serviceTypeId' => TestValue::BASIC_PEST_ROUTES_ID,
            'summerServiceIntervalDays' => 50,
            'winterServiceIntervalDays' => 50,
            'summerServicePeriodDays' => 30,
            'winterServicePeriodDays' => 30,
            'initialFollowUpDays' => TestValue::INITIAL_FOLLOW_UP_INTERVAL_DAYS,
        ],
        TestValue::PRO_ID => [
            'id' => TestValue::PRO_ID,
            'name' => TestValue::PRO,
            'serviceTypeId' => TestValue::PRO_PEST_ROUTES_ID,
            'summerServiceIntervalDays' => 30,
            'winterServiceIntervalDays' => 50,
            'summerServicePeriodDays' => 30,
            'winterServicePeriodDays' => 30,
            'initialFollowUpDays' => TestValue::INITIAL_FOLLOW_UP_INTERVAL_DAYS,
        ],
        TestValue::PRO_PLUS_ID => [
            'id' => TestValue::PRO_PLUS_ID,
            'name' => TestValue::PRO_PLUS,
            'serviceTypeId' => TestValue::PRO_PLUS_PEST_ROUTES_ID,
            'summerServiceIntervalDays' => 30,
            'winterServiceIntervalDays' => 50,
            'summerServicePeriodDays' => 30,
            'winterServicePeriodDays' => 30,
            'initialFollowUpDays' => TestValue::INITIAL_FOLLOW_UP_INTERVAL_DAYS,
        ],
        TestValue::PREMIUM_ID => [
            'id' => TestValue::PREMIUM_ID,
            'name' => TestValue::PREMIUM,
            'serviceTypeId' => TestValue::PREMIUM_PEST_ROUTES_ID,
            'summerServiceIntervalDays' => 20,
            'winterServiceIntervalDays' => 50,
            'summerServicePeriodDays' => 20,
            'winterServicePeriodDays' => 30,
            'initialFollowUpDays' => TestValue::INITIAL_FOLLOW_UP_INTERVAL_DAYS,
        ],
    ];

    /**
     * @test
     */
    public function it_returns_correct_plans(): void
    {
        $testPlans = collect([
            new Plan(...self::PLANS[TestValue::BASIC_ID]),
            new Plan(...self::PLANS[TestValue::PRO_ID]),
            new Plan(...self::PLANS[TestValue::PRO_PLUS_ID]),
            new Plan(...self::PLANS[TestValue::PREMIUM_ID]),
        ]);
        $staticPlansQuery = new StaticPlansQuery();
        $plans = $staticPlansQuery->get();

        $this->assertEquals($testPlans, $plans);
    }
}
