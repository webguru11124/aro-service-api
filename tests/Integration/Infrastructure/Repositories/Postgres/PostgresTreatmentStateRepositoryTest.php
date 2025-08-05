<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\Entities\ServicedRoute;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use App\Infrastructure\Repositories\Postgres\PostgresTreatmentStateRepository;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\Factories\Tracking\TreatmentStateFactory;
use Tests\Tools\Factories\Tracking\RouteDrivingStatsFactory;
use Tests\Tools\Factories\Tracking\ServicedRouteFactory;
use Tests\Tools\TestValue;

class PostgresTreatmentStateRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PostgresTreatmentStateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostgresTreatmentStateRepository();
    }

    /**
     * @test
     *
     * ::save
     */
    public function it_saves_data(): void
    {
        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make([
            'workdayId' => TestValue::WORKDAY_ID,
        ]);
        /** @var RouteDrivingStats $drivingStats */
        $drivingStats = RouteDrivingStatsFactory::make([
            'id' => $servicePro->getWorkdayId(),
        ]);
        /** @var ServicedRoute $servicedRoute */
        $servicedRoute = ServicedRouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'servicePro' => $servicePro,
        ]);

        /** @var TreatmentState $state */
        $state = TreatmentStateFactory::make([
            'servicedRoutes' => collect([$servicedRoute]),
            'drivingStats' => collect([$drivingStats]),
        ]);

        $this->repository->save($state);

        $this->assertDatabaseHas(PostgresDBInfo::TREATMENT_STATE_TABLE, [
            'as_of_date' => $state->getDate()->toDateString(),
            'stats' => json_encode($state->getSummary()->toArray()),
        ]);

        $this->assertDatabaseHas(PostgresDBInfo::SERVICED_ROUTE_DETAILS_TABLE, [
            'route_id' => $servicedRoute->getId(),
            'stats' => json_encode(
                array_merge($drivingStats->toArray(), $servicedRoute->getCompletionStats()->toArray())
            ),
        ]);
    }
}
