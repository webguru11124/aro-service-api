<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Managers;

use App\Application\DTO\ScheduleAppointmentsDTO;
use App\Application\Jobs\ScheduleAppointmentsJob;
use App\Application\Managers\ScheduleAppointmentsManager;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class ScheduleAppointmentsManagerTest extends TestCase
{
    private GetOfficesByIdsQuery|MockInterface $officeQueryMock;
    private FeatureFlagService|MockInterface $featureFlagServiceMock;
    private Office $office;
    private ScheduleAppointmentsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
            'name' => TestValue::OFFICE_NAME,
            'timezone' => TestValue::TIME_ZONE,
        ]);

        $this->officeQueryMock = Mockery::mock(GetOfficesByIdsQuery::class);
        $this->featureFlagServiceMock = Mockery::mock(FeatureFlagService::class);

        $this->manager = new ScheduleAppointmentsManager($this->officeQueryMock, $this->featureFlagServiceMock);
    }

    /**
     * @test
     */
    public function it_dispatches_appointment_scheduling_job(): void
    {
        $this->featureFlagServiceMock->shouldReceive('isFeatureEnabledForOffice')->andReturnTrue();
        $this->officeQueryMock
            ->shouldReceive('get')
            ->with([TestValue::OFFICE_ID])
            ->once()
            ->andReturn(collect([$this->office]));

        Log::shouldReceive('info')->once();

        $this->manager->manage(
            new ScheduleAppointmentsDTO([TestValue::OFFICE_ID], Carbon::today(TestValue::TIME_ZONE), 1)
        );

        Queue::assertPushed(ScheduleAppointmentsJob::class, function (ScheduleAppointmentsJob $job) {
            return $job->office->getId() === TestValue::OFFICE_ID
                && $job->date->toDateString() == Carbon::today(TestValue::TIME_ZONE)->toDateString();
        });
    }

    /**
     * @test
     */
    public function it_dispatches_appointment_scheduling_job_when_no_start_date_provided(): void
    {
        $this->featureFlagServiceMock->shouldReceive('isFeatureEnabledForOffice')->andReturnTrue();
        $this->officeQueryMock
            ->shouldReceive('get')
            ->with([TestValue::OFFICE_ID])
            ->once()
            ->andReturn(collect([$this->office]));

        Log::shouldReceive('info')->once();

        $this->manager->manage(
            new ScheduleAppointmentsDTO([TestValue::OFFICE_ID], null, 1)
        );

        Queue::assertPushed(ScheduleAppointmentsJob::class, function (ScheduleAppointmentsJob $job) {
            return $job->office->getId() === TestValue::OFFICE_ID
                && $job->date->toDateString() == Carbon::today(TestValue::TIME_ZONE)->addDay()->toDateString();
        });
    }

    /**
     * @test
     */
    public function it_does_not_dispatch_appointment_scheduling_job_if_scheduling_for_office_disabled_by_feature_flag(): void
    {
        $this->featureFlagServiceMock->shouldReceive('isFeatureEnabledForOffice')->andReturnFalse();
        $this->officeQueryMock
            ->shouldReceive('get')
            ->with([TestValue::OFFICE_ID])
            ->once()
            ->andReturn(collect([$this->office]));

        Log::shouldReceive('notice')->once();

        $this->manager->manage(
            new ScheduleAppointmentsDTO([TestValue::OFFICE_ID], Carbon::today(TestValue::TIME_ZONE), 1)
        );

        Queue::assertNotPushed(ScheduleAppointmentsJob::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->office);
        unset($this->officeQueryMock);
        unset($this->featureFlagServiceMock);
    }
}
