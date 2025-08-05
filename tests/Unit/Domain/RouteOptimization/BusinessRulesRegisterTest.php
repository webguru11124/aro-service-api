<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization;

use App\Domain\RouteOptimization\OptimizationRules\MustHaveInsideSales;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveRouteSummary;
use Illuminate\Support\Facades\Config;
use App\Domain\RouteOptimization\BusinessRulesRegister;
use App\Domain\RouteOptimization\OptimizationRules\AddExtraTimeToGetToFirstLocation;
use App\Domain\RouteOptimization\OptimizationRules\MustConsiderRoadTraffic;
use App\Domain\RouteOptimization\OptimizationRules\ExtendWorkingTime;
use App\Domain\RouteOptimization\OptimizationRules\IncreaseRouteCapacity;
use App\Domain\RouteOptimization\OptimizationRules\LockFirstAppointment;
use App\Domain\RouteOptimization\OptimizationRules\MustEndAtServiceProHomeLocation;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveBalancedWorkload;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveWorkBreaks;
use App\Domain\RouteOptimization\OptimizationRules\MustNotExceedMaxWorkingHours;
use App\Domain\RouteOptimization\OptimizationRules\VisitCalendarEventLocation;
use App\Domain\RouteOptimization\OptimizationRules\MustStartAtServiceProHomeLocation;
use App\Domain\RouteOptimization\OptimizationRules\RestrictTimeWindow;
use App\Domain\RouteOptimization\OptimizationRules\SetPreferredServicePro;
use App\Domain\RouteOptimization\OptimizationRules\SetServiceDurationToAverage;
use App\Domain\RouteOptimization\OptimizationRules\SetServiceDurationWithPredictiveModel;
use App\Domain\RouteOptimization\OptimizationRules\ShiftLockedAppointmentsTimeWindow;
use ConfigCat\ConfigCatClient;
use Tests\Integration\Application\FakeConfigCatClient;
use Tests\TestCase;

class BusinessRulesRegisterTest extends TestCase
{
    use FakeConfigCatClient;

    private const GENERAL_OPTIMIZATION_RULES = [
        MustConsiderRoadTraffic::class,
        MustNotExceedMaxWorkingHours::class,
        MustStartAtServiceProHomeLocation::class,
        MustEndAtServiceProHomeLocation::class,
        AddExtraTimeToGetToFirstLocation::class,
        VisitCalendarEventLocation::class,
        MustHaveRouteSummary::class,
        MustHaveInsideSales::class,
        MustHaveBalancedWorkload::class,
        MustHaveWorkBreaks::class,
        SetServiceDurationToAverage::class,
        SetServiceDurationWithPredictiveModel::class,
        LockFirstAppointment::class,
        RestrictTimeWindow::class,
        SetPreferredServicePro::class,
    ];

    private const ADDITIONAL_OPTIMIZATION_RULES = [
        IncreaseRouteCapacity::class,
        ExtendWorkingTime::class,
        ShiftLockedAppointmentsTimeWindow::class,
    ];

    private const GENERAL_PLAN_RULES = [
        MustConsiderRoadTraffic::class,
        MustStartAtServiceProHomeLocation::class,
        MustEndAtServiceProHomeLocation::class,
        SetServiceDurationToAverage::class,
    ];

    private BusinessRulesRegister $businessRulesRegister;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('open-weather-map.api_key', 'api_key');
        Config::set('open-weather-map.api_url', 'api_url');

        $this->businessRulesRegister = new BusinessRulesRegister();
        $MockConfigCatClient = $this->getFakeConfigCatClient();
        $this->instance(ConfigCatClient::class, $MockConfigCatClient);
    }

    /**
     * @test
     *
     * ::getGeneralOptimizationRules
     */
    public function it_returns_general_optimization_rules(): void
    {
        $rules = $this->businessRulesRegister->getGeneralOptimizationRules();

        $this->assertEquals(count(self::GENERAL_OPTIMIZATION_RULES), $rules->count());

        foreach ($rules as $rule) {
            $this->assertTrue(in_array($rule::class, self::GENERAL_OPTIMIZATION_RULES));
        }
    }

    /**
     * @test
     *
     * ::getAdditionalOptimizationRules
     */
    public function it_returns_additional_optimization_rules(): void
    {
        $rules = $this->businessRulesRegister->getAdditionalOptimizationRules();

        $this->assertEquals(count(self::ADDITIONAL_OPTIMIZATION_RULES), $rules->count());

        foreach ($rules as $rule) {
            $this->assertTrue(in_array($rule::class, self::ADDITIONAL_OPTIMIZATION_RULES));
        }
    }

    /**
     * @test
     *
     * ::getGeneralPlanRules
     */
    public function it_returns_general_plan_rules(): void
    {
        $rules = $this->businessRulesRegister->getGeneralPlanRules();

        $this->assertEquals(count(self::GENERAL_PLAN_RULES), $rules->count());

        foreach ($rules as $rule) {
            $this->assertTrue(in_array($rule::class, self::GENERAL_PLAN_RULES));
        }
    }
}
