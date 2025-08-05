<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Scheduling;

use App\Domain\Scheduling\Entities\Plan;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class PlanFactory extends AbstractFactory
{
    public function single($overrides = []): Plan
    {
        return new Plan(
            $overrides['id'] ?? TestValue::PLAN_DATA['id'],
            $overrides['name'] ?? TestValue::PLAN_DATA['name'],
            $overrides['serviceTypeId'] ?? TestValue::PLAN_DATA['serviceTypeId'],
            $overrides['summerServiceIntervalDays'] ?? TestValue::PLAN_DATA['summerServiceIntervalDays'],
            $overrides['winterServiceIntervalDays'] ?? TestValue::PLAN_DATA['winterServiceIntervalDays'],
            $overrides['summerServicePeriodDays'] ?? TestValue::PLAN_DATA['summerServicePeriodDays'],
            $overrides['winterServicePeriodDays'] ?? TestValue::PLAN_DATA['winterServicePeriodDays'],
            $overrides['initialFollowUpDays'] ?? TestValue::PLAN_DATA['initialFollowUpDays'],
        );
    }

    public static function all(): array
    {
        return [
            'BASIC' => new Plan(
                TestValue::BASIC_ID,
                TestValue::BASIC,
                TestValue::BASIC_PEST_ROUTES_ID,
                TestValue::PLAN_DATA['summerServiceIntervalDays'],
                TestValue::PLAN_DATA['winterServiceIntervalDays'],
                TestValue::PLAN_DATA['summerServicePeriodDays'],
                TestValue::PLAN_DATA['winterServicePeriodDays'],
                TestValue::PLAN_DATA['initialFollowUpDays'],
            ),
            'PRO' => new Plan(
                TestValue::PRO_ID,
                TestValue::PRO,
                TestValue::PRO_PEST_ROUTES_ID,
                TestValue::PLAN_DATA['summerServiceIntervalDays'],
                TestValue::PLAN_DATA['winterServiceIntervalDays'],
                TestValue::PLAN_DATA['summerServicePeriodDays'],
                TestValue::PLAN_DATA['winterServicePeriodDays'],
                TestValue::PLAN_DATA['initialFollowUpDays'],
            ),
            'PRO_PLUS' => new Plan(
                TestValue::PRO_PLUS_ID,
                TestValue::PRO_PLUS,
                TestValue::PRO_PLUS_PEST_ROUTES_ID,
                TestValue::PLAN_DATA['summerServiceIntervalDays'],
                TestValue::PLAN_DATA['winterServiceIntervalDays'],
                TestValue::PLAN_DATA['summerServicePeriodDays'],
                TestValue::PLAN_DATA['winterServicePeriodDays'],
                TestValue::PLAN_DATA['initialFollowUpDays'],
            ),
            'PREMIUM' => new Plan(
                TestValue::PREMIUM_ID,
                TestValue::PREMIUM,
                TestValue::PREMIUM_PEST_ROUTES_ID,
                TestValue::PLAN_DATA['summerServiceIntervalDays'],
                TestValue::PLAN_DATA['winterServiceIntervalDays'],
                TestValue::PLAN_DATA['summerServicePeriodDays'],
                TestValue::PLAN_DATA['winterServicePeriodDays'],
                TestValue::PLAN_DATA['initialFollowUpDays'],
            ),
        ];
    }
}
