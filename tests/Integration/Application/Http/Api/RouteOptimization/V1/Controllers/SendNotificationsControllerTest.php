<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\RouteOptimization\V1\Controllers;

use App\Application\DTO\ScoreNotificationsDTO;
use App\Application\Http\Api\RouteOptimization\V1\Controllers\ScoreNotificationsController;
use App\Application\Managers\ScoreNotificationsManager;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

/**
 * @coversDefaultClass ScoreNotificationsController
 */
class SendNotificationsControllerTest extends TestCase
{
    private const OFFICE_IDS = [
        2,
        TestValue::OFFICE_ID,
        120,
    ];
    private const DATE = '2023-06-23';
    private const ROUTE_NAME = 'score-notification-jobs.create';

    private ScoreNotificationsManager|MockInterface $mockScoreNotificationsManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockScoreNotificationsManager = Mockery::mock(ScoreNotificationsManager::class);
        $this->instance(ScoreNotificationsManager::class, $this->mockScoreNotificationsManager);
    }

    /**
     * @test
     */
    public function it_returns_expected_202(): void
    {
        $this->mockScoreNotificationsManager
            ->shouldReceive('manage')
            ->withArgs(function (ScoreNotificationsDTO $data) {
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
        $this->mockScoreNotificationsManager
            ->shouldReceive('manage')
            ->never();

        $response = $this->postJson(
            route(self::ROUTE_NAME),
            [],
        );

        $response->assertBadRequest();
    }

    /**
     * @test
     */
    public function it_returns_404_when_office_not_found(): void
    {
        $this->mockScoreNotificationsManager
            ->shouldReceive('manage')
            ->andThrow(new OfficeNotFoundException('Test exception'));

        $response = $this->postJson(
            route(self::ROUTE_NAME),
            ['office_ids' => self::OFFICE_IDS],
        );

        $response->assertNotFound();
        $response->assertJsonPath('_metadata.success', false);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->mockScoreNotificationsManager);
    }
}
