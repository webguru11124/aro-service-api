<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Actions;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Dto\CreateAppointmentDto;
use App\Infrastructure\Dto\RescheduleAppointmentDto;
use App\Infrastructure\Exceptions\AppointmentNotFoundException;
use App\Infrastructure\Exceptions\IVRSchedulerNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\EmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentRemindersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use App\Infrastructure\Services\PestRoutes\Actions\CreateAppointment;
use App\Infrastructure\Services\PestRoutes\Actions\RescheduleAppointment;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use App\Infrastructure\Services\PestRoutes\Enums\ServiceType;
use App\Infrastructure\Services\PestRoutes\Enums\Window;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\CreateAppointmentRemindersParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\TestValue;

class RescheduleAppointmentTest extends TestCase
{
    private CreateAppointment|MockInterface $createAppointmentActionMock;
    private PestRoutesAppointmentsDataProcessor|MockInterface $appointmentsDataProcessorMock;
    private EmployeesDataProcessor|MockInterface $employeesDataProcessorMock;
    private PestRoutesAppointmentRemindersDataProcessor|MockInterface $appointmentRemindersDataProcessorMock;
    private RescheduleAppointment $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAppointmentActionMock = \Mockery::mock(CreateAppointment::class);
        $this->appointmentsDataProcessorMock = \Mockery::mock(PestRoutesAppointmentsDataProcessor::class);
        $this->employeesDataProcessorMock = \Mockery::mock(EmployeesDataProcessor::class);
        $this->appointmentRemindersDataProcessorMock = \Mockery::mock(PestRoutesAppointmentRemindersDataProcessor::class);

        $this->action = new RescheduleAppointment(
            $this->createAppointmentActionMock,
            $this->appointmentsDataProcessorMock,
            $this->employeesDataProcessorMock,
            $this->appointmentRemindersDataProcessorMock
        );
    }

    /**
     * @test
     */
    public function it_reschedules_appointment(): void
    {
        /** @var Office $office */
        $office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);
        $source = RequestingSource::CUSTOMER_PORTAL;

        $dto = new RescheduleAppointmentDto(
            office: $office,
            customerId: TestValue::CUSTOMER_ID,
            spotId: TestValue::SPOT_ID,
            subscriptionId: TestValue::SUBSCRIPTION_ID,
            appointmentId: TestValue::APPOINTMENT_ID,
            serviceType: ServiceType::BASIC,
            isAroSpot: false,
            window: Window::AM,
            requestingSource: $source
        );

        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->withArgs(function (int $officeId, SearchAppointmentsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['appointmentIDs'] === [TestValue::APPOINTMENT_ID];
            })
            ->once()
            ->andReturn(AppointmentData::getTestData());

        $this->createAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(fn (CreateAppointmentDto $createDto) => $createDto->office === $dto->office
                && $createDto->window === $dto->window
                && $createDto->spotId === $dto->spotId
                && $createDto->appointmentType->name === $dto->serviceType->name
                && $createDto->subscriptionId === $dto->subscriptionId
                && $createDto->requestingSource === $dto->requestingSource
                && $createDto->isAroSpot === $dto->isAroSpot
                && $createDto->customerId === $dto->customerId)
            ->once()
            ->andReturn(TestValue::APPOINTMENT_ID + 1);

        $this->appointmentRemindersDataProcessorMock
            ->shouldReceive('create')
            ->withArgs(function (int $officeId, CreateAppointmentRemindersParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['appointmentID'] === TestValue::APPOINTMENT_ID + 1;
            })
            ->once()
            ->andReturn(TestValue::APPOINTMENT_REMINDER_ID);

        /** @var Employee $employee */
        $employee = EmployeeData::getTestData(1, ['employeeID' => TestValue::IVR_SCHEDULER_ID])->first();

        $this->employeesDataProcessorMock->shouldReceive('extractIVRScheduler')
            ->once()
            ->andReturn($employee);

        $this->appointmentsDataProcessorMock
            ->shouldReceive('cancel')
            ->with(
                TestValue::OFFICE_ID,
                TestValue::APPOINTMENT_ID,
                'Cancelled and rescheduled by customer via Flex IVR',
                TestValue::IVR_SCHEDULER_ID
            )
            ->once()
            ->andReturn(TestValue::APPOINTMENT_ID);

        ($this->action)($dto);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_appointment_not_found(): void
    {
        /** @var Office $office */
        $office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);
        $source = RequestingSource::CUSTOMER_PORTAL;

        $dto = new RescheduleAppointmentDto(
            office: $office,
            customerId: TestValue::CUSTOMER_ID,
            spotId: TestValue::SPOT_ID,
            subscriptionId: TestValue::SUBSCRIPTION_ID,
            appointmentId: TestValue::APPOINTMENT_ID,
            serviceType: ServiceType::BASIC,
            isAroSpot: false,
            window: Window::AM,
            requestingSource: $source
        );

        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->andReturn(new Collection());

        $this->expectException(AppointmentNotFoundException::class);

        ($this->action)($dto);
    }

    /**
     * @test
     */
    public function it_cancels_old_appointment_event_if_ivr_scheduler_not_found(): void
    {
        /** @var Office $office */
        $office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);
        $source = RequestingSource::CUSTOMER_PORTAL;

        $dto = new RescheduleAppointmentDto(
            office: $office,
            customerId: TestValue::CUSTOMER_ID,
            spotId: TestValue::SPOT_ID,
            subscriptionId: TestValue::SUBSCRIPTION_ID,
            appointmentId: TestValue::APPOINTMENT_ID,
            serviceType: ServiceType::BASIC,
            isAroSpot: false,
            window: Window::AM,
            requestingSource: $source
        );

        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->andReturn(AppointmentData::getTestData());

        $this->createAppointmentActionMock
            ->shouldReceive('__invoke')
            ->andReturn(TestValue::APPOINTMENT_ID + 1);

        $this->appointmentRemindersDataProcessorMock
            ->shouldReceive('create')
            ->andReturn(TestValue::APPOINTMENT_REMINDER_ID);

        $this->employeesDataProcessorMock->shouldReceive('extractIVRScheduler')
            ->once()
            ->andThrow(IVRSchedulerNotFoundException::instance());

        $this->appointmentsDataProcessorMock
            ->shouldReceive('cancel')
            ->with(
                TestValue::OFFICE_ID,
                TestValue::APPOINTMENT_ID,
                'Cancelled and rescheduled by customer via Flex IVR',
                null
            )
            ->once()
            ->andReturn(TestValue::APPOINTMENT_ID);

        ($this->action)($dto);
    }
}
