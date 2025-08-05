<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\PostOptimizationRules\MustHaveBlockedInsideSales;
use App\Domain\RouteOptimization\ValueObjects\RouteConfig;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesBlockedInsideSalesCaster;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;
use Tests\Traits\AssertRuleExecutionResultsTrait;

class PestRoutesBlockedInsideSalesCasterTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;

    private const PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG = 'isPestroutesSkipBuildEnabled';

    private PestRoutesBlockedInsideSalesCaster $caster;
    private MustHaveBlockedInsideSales $rule;

    private MockInterface|SpotsDataProcessor $mockSpotsDataProcessor;
    private MockInterface|AppointmentsDataProcessor $mockAppointmentsDataProcessor;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupMocks();

        $this->rule = new MustHaveBlockedInsideSales();

        $this->caster = new PestRoutesBlockedInsideSalesCaster(
            $this->mockSpotsDataProcessor,
            $this->mockAppointmentsDataProcessor,
            $this->mockFeatureFlagService,
        );
    }

    private function setupMocks(): void
    {
        $this->mockSpotsDataProcessor = Mockery::mock(SpotsDataProcessor::class);
        $this->mockAppointmentsDataProcessor = Mockery::mock(AppointmentsDataProcessor::class);

        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);
        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->withSomeOfArgs(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG)
            ->andReturnTrue();
    }

    /**
     * @test
     */
    public function it_blocks_spots_for_inside_sales_correctly(): void
    {
        $date = Carbon::today();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'config' => new RouteConfig(insideSales: 2, summary: 1),
        ]);
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        $spots = SpotData::getTestData(
            3,
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '16:30:00',
                'end' => '16:59:00',
            ],
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:00:00',
                'end' => '17:29:00',
            ],
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:30:00',
                'end' => '17:59:00',
            ],
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSpotsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['routeIDs'] === [TestValue::ROUTE_ID];
            })
            ->andReturn($spots);

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection());

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->once()
            ->withArgs(function (int $officeId, Collection $spots, string $blockReason) {
                return $officeId === TestValue::OFFICE_ID
                    && $spots->count() === 2
                    && $blockReason === PestRoutesBlockedInsideSalesCaster::BLOCK_REASON;
            });

        $result = $this->caster->process($date, $optimizationState, $this->rule);

        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_blocks_spots_for_inside_sales_when_all_spots_have_assigned_appointments(): void
    {
        $date = Carbon::today();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'config' => new RouteConfig(insideSales: 2, summary: 1),
        ]);
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        $spotSubstitution = [
            'routeID' => TestValue::ROUTE_ID,
        ];
        $spots = SpotData::getTestData(3, ...array_fill(0, 3, $spotSubstitution));
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($spots);

        $appointmentsSubstitution = $spots->map(fn (Spot $spot) => ['spotID' => $spot->id])->toArray();
        $appointments = AppointmentData::getTestData(3, ...$appointmentsSubstitution);
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($appointments);

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->never();

        $this->caster->process($date, $optimizationState, $this->rule);
    }

    /**
     * @test
     */
    public function it_stops_blocking_spots_when_encountering_a_spot_with_appointments(): void
    {
        $date = Carbon::today();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'config' => new RouteConfig(insideSales: 2, summary: 1),
        ]);
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        // Create 3 spots, the first and third have appointments, the second does not
        $spots = SpotData::getTestData(
            3,
            [
                'spotID' => TestValue::SPOT_ID + 1,
                'routeID' => TestValue::ROUTE_ID,
                'start' => '16:30:00',
                'end' => '16:59:00',
            ],
            [
                'spotID' => TestValue::SPOT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:00:00',
                'end' => '17:29:00',
            ],
            [
                'spotID' => TestValue::SPOT_ID + 2,
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:30:00',
                'end' => '17:59:00',
            ],
        );

        $appointments = AppointmentData::getTestData(
            3,
            ['spotID' => TestValue::SPOT_ID + 1],
            ['spotID' => TestValue::SPOT_ID + 2],
            ['spotID' => TestValue::SPOT_ID + 3],
        );
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($appointments);

        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($spots);

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->once()
            ->withArgs(function (int $officeId, Collection $spots, string $blockReason) {
                $correctSpots = $spots->filter(fn (Spot $spot) => $spot->id === TestValue::SPOT_ID);

                // Ensure the only one spot (without appointments) is blocked
                return $officeId === TestValue::OFFICE_ID
                    && $spots->count() === 1 && $correctSpots->count() === 1
                    && $blockReason === PestRoutesBlockedInsideSalesCaster::BLOCK_REASON;
            });

        $this->caster->process($date, $optimizationState, $this->rule);
    }

    /**
     * @test
     */
    public function it_blocks_spots_when_spot_has_allowed_blocked_reasons(): void
    {
        $date = Carbon::today();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'config' => new RouteConfig(insideSales: 2, summary: 1),
        ]);

        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        $spots = SpotData::getTestData(
            3,
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '16:30:00',
                'end' => '16:59:00',
                'blockReason' => 'Some inside sale',
                'spotCapacity' => 0,
            ],
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:00:00',
                'end' => '17:29:00',
                'blockReason' => 'Some reserved i.s.',
                'spotCapacity' => 0,
            ],
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:30:00',
                'end' => '17:59:00',
            ],
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($spots);

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection());

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->once()
            ->withArgs(function (int $officeId, Collection $spots, string $blockReason) {
                return $officeId === TestValue::OFFICE_ID
                    && $spots->count() === 2
                    && $blockReason === PestRoutesBlockedInsideSalesCaster::BLOCK_REASON;
            });

        $this->caster->process($date, $optimizationState, $this->rule);
    }

    /**
     * @test
     */
    public function it_blocks_spots_when_spot_has_empty_blocked_reasons(): void
    {
        $date = Carbon::today();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'config' => new RouteConfig(insideSales: 2, summary: 1),
        ]);

        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        $spots = SpotData::getTestData(
            3,
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '16:30:00',
                'end' => '16:59:00',
                'blockReason' => '',
                'spotCapacity' => 0,
            ],
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:00:00',
                'end' => '17:29:00',
                'blockReason' => '',
                'spotCapacity' => 0,
            ],
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:30:00',
                'end' => '17:59:00',
            ],
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($spots);

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection());

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->once()
            ->withArgs(function (int $officeId, Collection $spots, string $blockReason) {
                return $officeId === TestValue::OFFICE_ID
                    && $spots->count() === 2
                    && $blockReason === PestRoutesBlockedInsideSalesCaster::BLOCK_REASON;
            });

        $this->caster->process($date, $optimizationState, $this->rule);
    }

    /**
     * @test
     */
    public function it_does_not_block_spots_when_last_but_one_spot_has_blocked_reason(): void
    {
        $date = Carbon::today();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'config' => new RouteConfig(insideSales: 2, summary: 1),
        ]);

        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        // Create 3 spots, the first and second have blocked reasons
        $spots = SpotData::getTestData(
            3,
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '16:30:00',
                'end' => '16:59:00',
                'blockReason' => 'Some reason',
                'spotCapacity' => 0,
            ],
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:00:00',
                'end' => '17:29:00',
                'blockReason' => 'Some other reason',
                'spotCapacity' => 0,
            ],
            [
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:30:00',
                'end' => '17:59:00',
            ],
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($spots);

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection());

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->never();

        $this->caster->process($date, $optimizationState, $this->rule);
    }

    /**
     * @test
     */
    public function it_does_not_block_spots_when_last_but_one_spot_has_appointment_assigned(): void
    {
        $date = Carbon::today();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'config' => new RouteConfig(insideSales: 2, summary: 1),
        ]);

        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        // Create 3 spots, the second and third have appointments, the third does not
        $spots = SpotData::getTestData(
            3,
            [
                'spotID' => TestValue::SPOT_ID + 1,
                'routeID' => TestValue::ROUTE_ID,
                'start' => '16:30:00',
                'end' => '16:59:00',
            ],
            [
                'spotID' => TestValue::SPOT_ID + 2,
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:00:00',
                'end' => '17:29:00',
            ],
            [
                'spotID' => TestValue::SPOT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'start' => '17:30:00',
                'end' => '17:59:00',
            ],
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($spots);

        $appointments = AppointmentData::getTestData(
            3,
            ['spotID' => TestValue::SPOT_ID + 1],
            ['spotID' => TestValue::SPOT_ID + 2],
            ['spotID' => TestValue::SPOT_ID + 3],
        );
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($appointments);

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->never();

        $this->caster->process($date, $optimizationState, $this->rule);
    }

    /**
     * @test
     */
    public function it_does_not_block_spots_when_there_are_no_skilled_service_pro(): void
    {
        $date = Carbon::today();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'servicePro' => ServiceProFactory::make([
                'id' => TestValue::EMPLOYEE_ID,
                'skills' => [],
            ]),
            'config' => new RouteConfig(insideSales: 2, summary: 1),
        ]);

        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->never();

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->never();

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->never();

        $this->caster->process($date, $optimizationState, $this->rule);
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
        unset($this->mockSpotsDataProcessor);
        unset($this->mockAppointmentsDataProcessor);
        unset($this->mockFeatureFlagService);
    }
}
