<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Tracking\V1\Controllers;

use App\Application\Http\Api\Tracking\V1\Controllers\FleetRoutesController;
use App\Domain\Contracts\Queries\Office\GetOfficeQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\Tracking\Factories\TreatmentStateFactory;
use App\Domain\Tracking\ValueObjects\TreatmentStateIdentity;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

/**
 * @coversDefaultClass FleetRoutesController
 */
class FleetRoutesControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const DATE = '2022-01-01';
    private const ROUTE_NAME = 'tracking.fleet-routes.index';

    private TreatmentStateFactory|MockInterface $mockTreatmentStateFactory;
    private GetOfficeQuery|MockInterface $mockOfficeQuery;

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];

        $this->setupMockTreatmentStateFactory();
        $this->setupMockOfficeRepository();
    }

    private function setupMockTreatmentStateFactory(): void
    {
        $this->mockTreatmentStateFactory = Mockery::mock(TreatmentStateFactory::class);
        $this->instance(TreatmentStateFactory::class, $this->mockTreatmentStateFactory);
    }

    private function setupMockOfficeRepository(): void
    {
        $this->mockOfficeQuery = Mockery::mock(GetOfficeQuery::class);
        $this->instance(GetOfficeQuery::class, $this->mockOfficeQuery);
    }

    /**
     * @test
     */
    public function it_returns_200_and_all_fleet_routes(): void
    {
        $this->mockOfficeQuery
            ->shouldReceive('get')
            ->with(TestValue::OFFICE_ID)
            ->once()
            ->andReturn(OfficeFactory::make(['id' => TestValue::OFFICE_ID]));

        $state = \Tests\Tools\Factories\Tracking\TreatmentStateFactory::make([
            'id' => new TreatmentStateIdentity(TestValue::OFFICE_ID, Carbon::parse(self::DATE)),
        ]);

        $this->mockTreatmentStateFactory
            ->shouldReceive('create')
            ->withArgs(
                fn (Office $office, CarbonInterface $date)
                    => $office->getId() === TestValue::OFFICE_ID
                    && $date->toDateString() === self::DATE
            )
            ->once()
            ->andReturns($state);

        $response = $this->callGetFleetRoutes([
            'office_id' => TestValue::OFFICE_ID,
            'date' => self::DATE,
        ]);

        $response->assertOk();
        $response->assertJsonPath('_metadata.success', true);
    }

    /**
     * @test
     */
    public function it_returns_400_when_invalid_parameters_passed(): void
    {
        $response = $this->callGetFleetRoutes([]);

        $response->assertBadRequest();
    }

    /**
     * @param array<string, mixed> $requestData
     *
     * @return TestResponse
     */
    private function callGetFleetRoutes(array $requestData): TestResponse
    {
        return $this
            ->withHeaders($this->getHeaders())
            ->get(route(self::ROUTE_NAME, $requestData));
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockTreatmentStateFactory);
        unset($this->mockOfficeQuery);
    }
}
