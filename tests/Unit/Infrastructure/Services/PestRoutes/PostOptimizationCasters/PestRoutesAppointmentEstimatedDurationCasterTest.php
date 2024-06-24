<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Services\ConfigCat\ConfigCatService;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesAppointmentEstimatedDurationCaster;
use App\Domain\RouteOptimization\PostOptimizationRules\SetAppointmentEstimatedDuration;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\TestValue;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use Tests\Traits\AssertRuleExecutionResultsTrait;

class PestRoutesAppointmentEstimatedDurationCasterTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;

    private const APPOINTMENT_TEST_ID = 14;

    private PestRoutesAppointmentEstimatedDurationCaster $caster;
    private SetAppointmentEstimatedDuration $rule;
    private AppointmentsDataProcessor|MockInterface $mockPestRoutesAppointmentDataProcessor;
    private FeatureFlagService|MockInterface $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupRule();
        $this->setupMocks();

        $this->caster = new PestRoutesAppointmentEstimatedDurationCaster(
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
        $this->rule = new SetAppointmentEstimatedDuration();
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
                'office_id' => TestValue::OFFICE_ID,
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
                'office_id' => TestValue::OFFICE_ID,
                'optimizationParams' => new OptimizationParams(false),
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
    public function it_applies_rule_correctly(Appointment $appointment): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturn(true);

        $duration = 18;

        $pestRoutesAppointments = AppointmentData::getTestData(1, [
            'duration' => $duration,
            'appointmentID' => self::APPOINTMENT_TEST_ID,
            'notes' => 'Initial Notes',
            'officeID' => TestValue::OFFICE_ID,
        ]);

        $route = RouteFactory::make([
            'id' => $pestRoutesAppointments->first()->routeId,
            'workEvents' => [
                $appointment,
            ],
        ]);

        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
            'optimizationParams' => new OptimizationParams(true),
        ]);

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($pestRoutesAppointments);

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('update')
            ->once()
            ->withArgs(
                function (int $officeId, UpdateAppointmentsParams $params) use ($pestRoutesAppointments) {
                    $array = $params->toArray();
                    $pestRoutesAppointment = $pestRoutesAppointments->first();

                    return $officeId === TestValue::OFFICE_ID
                        && $array['appointmentID'] == $pestRoutesAppointment->id
                        && !empty($array['duration'])
                        && !empty($array['notes']);
                }
            );

        $result = $this->caster->process(Carbon::today(), $optimizationState, $this->rule);

        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly_and_dont_overwrite_duration_on_notes(): void
    {
        $appointment = AppointmentFactory::make([
            'id' => self::APPOINTMENT_TEST_ID,
            'duration' => Duration::fromMinutes(22),
        ]);
        $notes = '';
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->twice()
            ->andReturnTrue();

        $duration = 18;

        $pestRoutesAppointments = AppointmentData::getTestData(1, [
            'duration' => $duration,
            'appointmentID' => self::APPOINTMENT_TEST_ID,
            'notes' => 'Initial Notes',
            'officeID' => TestValue::OFFICE_ID,
        ]);

        $route = RouteFactory::make([
            'id' => $pestRoutesAppointments->first()->routeId,
            'workEvents' => [
                $appointment,
            ],
        ]);

        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
            'optimizationParams' => new OptimizationParams(true),
        ]);

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('extract')
            ->twice()
            ->andReturn($pestRoutesAppointments);

        $this->mockPestRoutesAppointmentDataProcessor
            ->shouldReceive('update')
            ->twice()
            ->withArgs(
                function (int $officeId, UpdateAppointmentsParams $params) use ($pestRoutesAppointments, &$notes) {
                    $array = $params->toArray();
                    $pestRoutesAppointment = $pestRoutesAppointments->first();
                    $notes = $array['notes'];

                    return $officeId === TestValue::OFFICE_ID
                        && $array['appointmentID'] == $pestRoutesAppointment->id
                        && !empty($array['duration'])
                        && !empty($notes);
                }
            );

        $this->caster->process(Carbon::today(), $optimizationState, $this->rule);
        $this->caster->process(Carbon::today(), $optimizationState, $this->rule);

        $durationOccurences = substr_count($notes, 'Minimum Duration');
        $this->assertEquals(1, $durationOccurences);
    }

    public static function appointmentDataProvider(): iterable
    {
        $duration = 22;

        /** @var Appointment $appointment */
        $appointment = AppointmentFactory::make([
            'id' => self::APPOINTMENT_TEST_ID,
            'duration' => Duration::fromMinutes($duration),
        ]);

        yield [$appointment];

        $appointment2 = AppointmentFactory::make([
            'id' => self::APPOINTMENT_TEST_ID,
        ]);

        $propertyDetails = new PropertyDetails(
            100000.0,
            209000.0,
            10500.0,
        );

        $appointment2->resolveServiceDuration($propertyDetails, $duration, null);

        yield [$appointment2];
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
