<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\Entities\FleetRouteState;
use App\Domain\Tracking\Factories\FleetRouteFactory;
use App\Infrastructure\Repositories\Postgres\PostgresFleetRouteStateRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\OptimizationStateSeeder;
use Tests\Tools\DatabaseSeeders\RouteDetailsSeeder;
use Tests\Tools\DatabaseSeeders\RouteGeometrySeeder;
use Tests\Tools\DatabaseSeeders\ServicedRouteDetailsSeeder;

class PostgresFleetRouteStateRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PostgresFleetRouteStateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostgresFleetRouteStateRepository(
            app()->make(FleetRouteFactory::class)
        );

        $this->seed([
            OptimizationStateSeeder::class,
            ServicedRouteDetailsSeeder::class,
            RouteDetailsSeeder::class,
            RouteGeometrySeeder::class,
        ]);
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_searches_by_existing_office_id_and_date(): void
    {
        $officeId = 95;
        $date = Carbon::parse(OptimizationStateSeeder::getOptimizationStatesMockData()['as_of_date'][1]);

        /** @var FleetRouteState $result */
        $result = $this->repository->findByOfficeIdAndDate($officeId, $date);

        $this->assertNotEmpty($result);
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_searches_by_non_existing_office_id_and_date(): void
    {
        $officeId = 94;
        $date = Carbon::parse(OptimizationStateSeeder::getOptimizationStatesMockData()['as_of_date'][0]);

        /** @var Collection<FleetRoute> $result */
        $result = $this->repository->findByOfficeIdAndDate($officeId, $date);

        $this->assertEmpty($result);
    }
}
