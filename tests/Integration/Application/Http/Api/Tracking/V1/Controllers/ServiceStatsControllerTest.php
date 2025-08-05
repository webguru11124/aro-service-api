<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Tracking\V1\Controllers;

use App\Application\DTO\ServiceStatsDTO;
use App\Application\Http\Api\Tracking\V1\Controllers\ServiceStatsController;
use App\Application\Managers\ServiceStatsManager;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

/**
 * @coversDefaultClass ServiceStatsController
 */
class ServiceStatsControllerTest extends TestCase
{
    private const OFFICE_IDS = [
        2,
        TestValue::OFFICE_ID,
        120,
    ];

    private const DATE = '2023-06-23';
    private const ROUTE_NAME = 'service-stats-jobs.create';

    private ServiceStatsManager|MockInterface $mockServiceStatsManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockServiceStatsManager = Mockery::mock(ServiceStatsManager::class);
        $this->instance(ServiceStatsManager::class, $this->mockServiceStatsManager);
    }

    /**
     * @test
     */
    public function it_returns_expected_202(): void
    {
        $this->mockServiceStatsManager
            ->shouldReceive('manage')
            ->withArgs(function (ServiceStatsDTO $data) {
                return $data->officeIds === self::OFFICE_IDS
                    && $data->date->toDateString() === self::DATE;
            })
            ->once()
            ->andReturnNull();

        $response = $this->postJson(
            route(self::ROUTE_NAME),
            [
                'office_ids' => self::OFFICE_IDS,
                'date' => self::DATE,
            ],
        );

        $response->assertAccepted();
        $response->assertJsonPath('_metadata.success', true);
    }

    /**
     * @test
     */
    public function it_returns_400_when_invalid_parameters_passed(): void
    {
        $this->mockServiceStatsManager
            ->shouldReceive('manage')
            ->never();

        $response = $this->postJson(
            route(self::ROUTE_NAME),
            [],
        );

        $response->assertBadRequest();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->mockServiceStatsManager);
    }
}
