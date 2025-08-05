<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\Scheduling\Entities\Plan;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\TestValue;

class PlanTest extends TestCase
{
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plan = new Plan(
            TestValue::PLAN_DATA['id'],
            TestValue::PLAN_DATA['name'],
            TestValue::PLAN_DATA['serviceTypeId'],
            TestValue::PLAN_DATA['summerServiceIntervalDays'],
            TestValue::PLAN_DATA['winterServiceIntervalDays'],
            TestValue::PLAN_DATA['summerServicePeriodDays'],
            TestValue::PLAN_DATA['winterServicePeriodDays'],
            TestValue::PLAN_DATA['initialFollowUpDays'],
        );
    }

    /**
     * @test
     */
    public function it_returns_correct_values(): void
    {
        $this->assertEquals(TestValue::PLAN_DATA['id'], $this->plan->getId());
        $this->assertEquals(TestValue::PLAN_DATA['name'], $this->plan->getName());
        $this->assertEquals(TestValue::PLAN_DATA['serviceTypeId'], $this->plan->getServiceTypeId());
        $this->assertEquals(TestValue::PLAN_DATA['summerServiceIntervalDays'], $this->plan->getSummerServiceIntervalDays());
        $this->assertEquals(TestValue::PLAN_DATA['winterServiceIntervalDays'], $this->plan->getWinterServiceIntervalDays());
        $this->assertEquals(TestValue::PLAN_DATA['summerServicePeriodDays'], $this->plan->getSummerServicePeriodDays());
        $this->assertEquals(TestValue::PLAN_DATA['initialFollowUpDays'], $this->plan->getInitialFollowUpDays());
    }

    /**
     * @test
     *
     * @dataProvider periodProvider
     */
    public function test_is_winter_period(string $date, int $expectedInterval, int $expectedPeriod)
    {
        $date = Carbon::parse($date);

        $this->assertEquals($expectedInterval, $this->plan->getServiceIntervalDays($date), "Failed interval days check for $date");
        $this->assertEquals($expectedPeriod, $this->plan->getServicePeriodDays($date), "Failed period days check for $date");
    }

    public static function periodProvider()
    {
        return [
            'Mid-Winter' => ['2023-01-15', TestValue::PLAN_DATA['winterServiceIntervalDays'], TestValue::PLAN_DATA['winterServicePeriodDays']],
            'End-Winter' => ['2023-03-30', TestValue::PLAN_DATA['winterServiceIntervalDays'], TestValue::PLAN_DATA['winterServicePeriodDays']],
            'Start-Summer' => ['2023-04-01', TestValue::PLAN_DATA['summerServiceIntervalDays'], TestValue::PLAN_DATA['summerServicePeriodDays']],
            'Mid-Summer' => ['2023-07-15', TestValue::PLAN_DATA['summerServiceIntervalDays'], TestValue::PLAN_DATA['summerServicePeriodDays']],
            'End-Summer' => ['2023-10-30', TestValue::PLAN_DATA['summerServiceIntervalDays'], TestValue::PLAN_DATA['summerServicePeriodDays']],
            'Start-Winter' => ['2023-11-01', TestValue::PLAN_DATA['winterServiceIntervalDays'], TestValue::PLAN_DATA['winterServicePeriodDays']],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->plan);
    }
}
