<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Jobs;

use App\Application\Commands\CreateEmptyRoute\CreateEmptyRoutesCommand;
use App\Application\Commands\CreateEmptyRoute\CreateEmptyRoutesHandler;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobEnded;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobStarted;
use App\Application\Jobs\RoutesCreationJob;
use App\Domain\Contracts\Queries\EmployeeInfoQuery;
use App\Domain\Contracts\Queries\GetRoutesByOfficeAndDateQuery;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\EmployeeFactory;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\TestValue;

class RoutesCreationJobTest extends TestCase
{
    private Carbon $date;
    private Office $office;
    private RoutesCreationJob $job;

    private MockInterface|GetRoutesByOfficeAndDateQuery $getRoutesByOfficeAndDateQueryMock;
    private MockInterface|EmployeeInfoQuery $employeeInfoQueryMock;
    private MockInterface|CreateEmptyRoutesHandler $createEmptyRouteHandlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->setupMocks();

        $this->date = Carbon::tomorrow();
        $this->office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);
        $this->job = new RoutesCreationJob($this->date, $this->office);
    }

    private function setupMocks(): void
    {
        $this->getRoutesByOfficeAndDateQueryMock = Mockery::mock(GetRoutesByOfficeAndDateQuery::class);
        $this->employeeInfoQueryMock = Mockery::mock(EmployeeInfoQuery::class);
        $this->createEmptyRouteHandlerMock = Mockery::mock(CreateEmptyRoutesHandler::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_creates_routes(): void
    {
        $this->getRoutesByOfficeAndDateQueryMock
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                fn (Office $office, Carbon $date) => $date->toDateString() === $this->date->toDateString()
                    && $office->getId() === $this->office->getId()
            )
            ->andReturn(collect([]));

        $employees = EmployeeFactory::many(2);
        $this->employeeInfoQueryMock
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect($employees));

        $this->createEmptyRouteHandlerMock
            ->shouldReceive('handle')
            ->once()
            ->withArgs(function (CreateEmptyRoutesCommand $command) use ($employees) {
                return $command->office->getId() === $this->office->getId()
                    && $command->date->toDateString() === $this->date->toDateString()
                    && $command->employees->toArray() == $employees;
            });

        Log::shouldReceive('notice')
            ->never();

        $this->runJob();

        Event::assertDispatched(RoutesCreationJobStarted::class);
        Event::assertDispatched(RoutesCreationJobEnded::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_skips_creation_if_routes_exist(): void
    {
        $this->getRoutesByOfficeAndDateQueryMock
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                fn (Office $office, Carbon $date) => $date->toDateString() === $this->date->toDateString()
                    && $office->getId() === $this->office->getId()
            )
            ->andReturn(collect(RouteFactory::many(3)));

        $this->employeeInfoQueryMock
            ->shouldReceive('get')
            ->never();

        $this->createEmptyRouteHandlerMock
            ->shouldReceive('handle')
            ->never();

        Log::shouldReceive('notice')
            ->once();

        $this->runJob();

        Event::assertDispatched(RoutesCreationJobStarted::class);
        Event::assertDispatched(RoutesCreationJobEnded::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_catches_source_data_validation_exception(): void
    {
        $this->getRoutesByOfficeAndDateQueryMock
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                fn (Office $office, Carbon $date) => $date->toDateString() === $this->date->toDateString()
                    && $office->getId() === $this->office->getId()
            )
            ->andReturn(collect([]));

        $this->employeeInfoQueryMock
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect([]));

        $this->createEmptyRouteHandlerMock
            ->shouldReceive('handle')
            ->never();

        Log::shouldReceive('notice')
            ->once();

        $this->runJob();

        Event::assertDispatched(RoutesCreationJobStarted::class);
        Event::assertDispatched(RoutesCreationJobEnded::class);
    }

    private function runJob(): void
    {
        $this->job->handle(
            $this->getRoutesByOfficeAndDateQueryMock,
            $this->employeeInfoQueryMock,
            $this->createEmptyRouteHandlerMock,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->job,
            $this->getRoutesByOfficeAndDateQueryMock,
            $this->employeeInfoQueryMock,
            $this->createEmptyRouteHandlerMock,
        );
    }
}
