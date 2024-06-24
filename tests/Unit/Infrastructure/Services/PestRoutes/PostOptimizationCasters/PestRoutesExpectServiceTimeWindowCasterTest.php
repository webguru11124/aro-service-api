<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\PostOptimizationRules\SetExpectServiceTimeWindow;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Services\ConfigCat\ConfigCatService;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesExpectServiceTimeWindowCaster;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentTimeWindow;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Traits\AssertRuleExecutionResultsTrait;

class PestRoutesExpectServiceTimeWindowCasterTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;

    private const TEST_OFFICE_ID = 57;
    private const START_AT = '09:00:00';
    private const END_AT = '10:00:00';

    private PestRoutesExpectServiceTimeWindowCaster $caster;
    private SetExpectServiceTimeWindow $rule;
    private AppointmentsDataProcessor|MockInterface $mockPestRoutesAppointmentDataProcessor;
    private FeatureFlagService|MockInterface $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupRule();
        $this->setupMocks();

        $this->caster = new PestRoutesExpectServiceTimeWindowCaster(
            $this->mockPestRoutesAppointmentDataProcessor,
            $this->mockFeatureFlagService,
        );
    }

    private function setupMocks(): void
    {
        $this->mockPestRoutesAppointmentDataProcessor = Mockery::mock(AppointmentsDataProcessor::class);
        $this->mockFeatureFlagService = Mockery::mock(ConfigCatService::class);
    }

    private function setupRule(): void
    {
        $this->rule = new SetExpectServiceTimeWindow();
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_when_feature_flag_is_disabled(): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturn(false);

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('extract')
            ->never();

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('update')
            ->never();

        $result = $this->caster->process(
            Carbon::today(),
            OptimizationStateFactory::make([
                'office_id' => self::TEST_OFFICE_ID,
                'optimizationParams' => new OptimizationParams(true),
            ]),
            $this->rule
        );

        $this->assertTriggeredRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_when_it_is_not_last_optimization_run(): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->never();

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('extract')
            ->never();

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('update')
            ->never();

        $result = $this->caster->process(
            Carbon::today(),
            OptimizationStateFactory::make([
                'office_id' => self::TEST_OFFICE_ID,
                'lastOptimizationRun' => false,
            ]),
            $this->rule
        );

        $this->assertTriggeredRuleResult($result);
    }

    /**
     * @test
     *
     * @dataProvider appointmentDataProvider
     */
    public function it_applies_rule_correctly(AppointmentTimeWindow $timeWindow): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturn(true);

        $minutes = $this->rule->getTimeWindowMinutes()->getTotalMinutes();
        $appointmentId = $this->faker->randomNumber(5);
        $route = RouteFactory::make([
            'workEvents' => [
                AppointmentFactory::make([
                    'id' => $appointmentId,
                    'timeWindow' => new TimeWindow(
                        Carbon::now()->setTimeFromTimeString(self::START_AT),
                        Carbon::now()->setTimeFromTimeString(self::END_AT)
                    ),
                ]),
            ],
        ]);
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => self::TEST_OFFICE_ID,
            'routes' => [$route],
            'optimizationParams' => new OptimizationParams(true),
        ]);

        $pestRoutesAppointments = AppointmentData::getTestData(1, [
            'appointmentID' => $appointmentId,
            'timeWindow' => $timeWindow->value,
            'officeID' => self::TEST_OFFICE_ID,
        ]);

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($pestRoutesAppointments);

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('update')
            ->once()
            ->withArgs(
                function (int $officeId, UpdateAppointmentsParams $params) use ($minutes) {
                    $array = $params->toArray();
                    $startAt = Carbon::today()->setTimeFromTimeString(self::START_AT);

                    return $officeId === self::TEST_OFFICE_ID
                        && $array['start'] == $startAt->toDateTimeString()
                        && $array['end'] == $startAt->addMinutes($minutes)->toDateTimeString()
                    ;
                }
            );

        $result = $this->caster->process(Carbon::today(), $optimizationState, $this->rule);

        $this->assertSuccessRuleResult($result);
    }

    public static function appointmentDataProvider(): iterable
    {
        yield [
            AppointmentTimeWindow::Anytime,
        ];
        yield [
            AppointmentTimeWindow::AM,
        ];
        yield [
            AppointmentTimeWindow::PM,
        ];
    }

    protected function getClassRuleName(): string
    {
        return get_class($this->rule);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->caster);
        unset($this->rule);
        unset($this->mockPestRoutesAppointmentDataProcessor);
        unset($this->mockFeatureFlagService);
    }
}
