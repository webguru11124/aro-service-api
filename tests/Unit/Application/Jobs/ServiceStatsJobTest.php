<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Jobs;

use App\Application\Jobs\ServiceStatsJob;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\Tracking\Exceptions\FailedPublishTrackingDataException;
use App\Domain\Tracking\Factories\TreatmentStateFactory;
use App\Infrastructure\Repositories\Postgres\PostgresTreatmentStateRepository;
use App\Infrastructure\Services\WebsocketTracking\WebsocketTrackingService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class ServiceStatsJobTest extends TestCase
{
    private CarbonInterface $date;
    private Office $office;
    private ServiceStatsJob $job;

    private TreatmentStateFactory|MockInterface $mockTreatmentStateFactory;
    private WebsocketTrackingService|MockInterface $mockTrackingService;
    private PostgresTreatmentStateRepository|MockInterface $mockTreatmentStateRepository;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->date = Carbon::today();
        $this->office = OfficeFactory::make();

        $this->job = new ServiceStatsJob($this->date, $this->office);
        $this->mockTreatmentStateFactory = Mockery::mock(TreatmentStateFactory::class);
        $this->mockTrackingService = Mockery::mock(WebsocketTrackingService::class);
        $this->mockTreatmentStateRepository = Mockery::mock(PostgresTreatmentStateRepository::class);
    }

    /**
     * @test
     */
    public function it_updates_actual_data_for_fleet_route(): void
    {
        $state = \Tests\Tools\Factories\Tracking\TreatmentStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
        ]);

        $this->mockTreatmentStateFactory
            ->shouldReceive('create')
            ->with($this->office, $this->date)
            ->once()
            ->andReturn($state);
        $this->mockTrackingService
            ->shouldReceive('publish')
            ->once()
            ->with($state);
        $this->mockTreatmentStateRepository
            ->shouldReceive('save')
            ->once()
            ->with($state);

        $this->executeJob();
    }

    /**
     * @test
     */
    public function it_handles_exception_when_failed_to_publish_tracking_data(): void
    {
        $state = \Tests\Tools\Factories\Tracking\TreatmentStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
        ]);

        $this->mockTreatmentStateFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($state);
        $this->mockTrackingService
            ->shouldReceive('publish')
            ->andThrow(new FailedPublishTrackingDataException('Failed to publish tracking data'));
        $this->mockTreatmentStateRepository
            ->shouldReceive('save')
            ->never();

        Log::shouldReceive('notice')->once();

        $this->executeJob();
    }

    private function executeJob(): void
    {
        $this->job->handle(
            $this->mockTrackingService,
            $this->mockTreatmentStateFactory,
            $this->mockTreatmentStateRepository,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->job);
        unset($this->date);
        unset($this->office);
        unset($this->mockTreatmentStateFactory);
        unset($this->mockTrackingService);
        unset($this->mockTreatmentStateRepository);
    }
}
