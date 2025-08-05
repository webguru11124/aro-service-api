<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Actions;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Dto\CreateAppointmentDto;
use App\Infrastructure\Exceptions\SpotNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\EmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use App\Infrastructure\Services\PestRoutes\Actions\CreateAppointment;
use App\Infrastructure\Services\PestRoutes\Enums\AppointmentType;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use App\Infrastructure\Services\PestRoutes\Enums\ServiceType;
use App\Infrastructure\Services\PestRoutes\Enums\Window;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;

class CreateAppointmentTest extends TestCase
{
    private const DEFAULT_DURATION = 30;

    private PestRoutesAppointmentsDataProcessor|MockInterface $appointmentsDataProcessorMock;
    private EmployeesDataProcessor|MockInterface $employeesDataProcessorMock;
    private SpotsDataProcessor|MockInterface $spotsDataProcessorMock;
    private CreateAppointment $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointmentsDataProcessorMock = \Mockery::mock(PestRoutesAppointmentsDataProcessor::class);
        $this->employeesDataProcessorMock = \Mockery::mock(EmployeesDataProcessor::class);
        $this->spotsDataProcessorMock = \Mockery::mock(SpotsDataProcessor::class);

        $this->action = new CreateAppointment(
            $this->appointmentsDataProcessorMock,
            $this->employeesDataProcessorMock,
            $this->spotsDataProcessorMock
        );
    }

    /**
     * @test
     */
    public function it_creates_appointment(): void
    {
        /** @var Office $office */
        $office = OfficeFactory::make();
        $source = RequestingSource::TEST;
        $notes = 'TestNotes';
        $date = '2024-01-01';

        $dto = new CreateAppointmentDto(
            office: $office,
            customerId: TestValue::CUSTOMER_ID,
            spotId: TestValue::SPOT_ID,
            subscriptionId: TestValue::SUBSCRIPTION_ID,
            appointmentType: AppointmentType::BASIC,
            isAroSpot: false,
            window: Window::AM,
            requestingSource: $source,
            notes: $notes
        );

        /** @var Employee $employee */
        $employee = EmployeeData::getTestData()->first();

        $this->employeesDataProcessorMock->shouldReceive('extractIVRScheduler')
            ->with($office->getId())
            ->once()
            ->andReturn($employee);

        $spots = SpotData::getTestData(1, [
            'spotID' => TestValue::SPOT_ID,
            'date' => $date,
        ]);

        $this->spotsDataProcessorMock->shouldReceive('extract')
            ->once()
            ->andReturn($spots);

        $this->appointmentsDataProcessorMock->shouldReceive('create')
            ->withArgs(function (int $officeId, CreateAppointmentsParams $params) use ($employee, $source, $notes) {
                $array = $params->toArray();

                return $array['customerID'] === TestValue::CUSTOMER_ID
                    && $array['type'] === ServiceType::BASIC->value
                    && $array['start'] === '2024-01-01 08:00:00'
                    && $array['end'] === '2024-01-01 12:00:00'
                    && $array['duration'] === self::DEFAULT_DURATION
                    && $array['employeeID'] === $employee->id
                    && $array['notes'] === sprintf('Scheduled via %s - %s', $source->value, $notes)
                    && $array['spotID'] === TestValue::SPOT_ID
                    && $array['subscriptionID'] === TestValue::SUBSCRIPTION_ID
                    && $array['officeID'] === $officeId;
            })
            ->once()
            ->andReturn(TestValue::APPOINTMENT_ID);

        ($this->action)($dto);
    }

    /**
     * @test
     */
    public function it_resolves_window_correctly(): void
    {
        $dto = new CreateAppointmentDto(
            office: OfficeFactory::make(),
            customerId: TestValue::CUSTOMER_ID,
            spotId: TestValue::SPOT_ID,
            subscriptionId: TestValue::SUBSCRIPTION_ID,
            appointmentType: AppointmentType::BASIC,
            isAroSpot: false,
            window: Window::PM,
            requestingSource: RequestingSource::TEST,
            notes: null
        );

        $this->employeesDataProcessorMock
            ->shouldReceive('extractIVRScheduler')
            ->andReturn(EmployeeData::getTestData()->first());

        $this->spotsDataProcessorMock
            ->shouldReceive('extract')
            ->andReturn(SpotData::getTestData());

        $this->appointmentsDataProcessorMock->shouldReceive('create')
            ->withArgs(function (int $officeId, CreateAppointmentsParams $params) {
                $array = $params->toArray();

                return str_contains($array['start'], '12:00:00')
                    && str_contains($array['end'], '17:00:00');
            })
            ->once()
            ->andReturn(TestValue::APPOINTMENT_ID);

        ($this->action)($dto);
    }

    /**
     * @test
     */
    public function it_unblocks_aro_spot_before_creating_an_appointment(): void
    {
        $dto = new CreateAppointmentDto(
            office: OfficeFactory::make(),
            customerId: TestValue::CUSTOMER_ID,
            spotId: TestValue::SPOT_ID,
            subscriptionId: TestValue::SUBSCRIPTION_ID,
            appointmentType: AppointmentType::BASIC,
            isAroSpot: true,
            window: Window::AM,
            requestingSource: RequestingSource::TEST,
            notes: null
        );

        $this->employeesDataProcessorMock
            ->shouldReceive('extractIVRScheduler')
            ->andReturn(EmployeeData::getTestData()->first());

        $this->spotsDataProcessorMock
            ->shouldReceive('extract')
            ->andReturn(SpotData::getTestData());

        $this->spotsDataProcessorMock
            ->shouldReceive('unblock')
            ->once();

        $this->appointmentsDataProcessorMock->shouldReceive('create')
            ->andReturn(TestValue::APPOINTMENT_ID);

        ($this->action)($dto);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_spot_not_found(): void
    {
        $dto = new CreateAppointmentDto(
            office: OfficeFactory::make(),
            customerId: TestValue::CUSTOMER_ID,
            spotId: TestValue::SPOT_ID,
            subscriptionId: TestValue::SUBSCRIPTION_ID,
            appointmentType: AppointmentType::BASIC,
            isAroSpot: false,
            window: Window::PM,
            requestingSource: RequestingSource::TEST,
            notes: null
        );

        $this->spotsDataProcessorMock
            ->shouldReceive('extract')
            ->andReturn(new Collection());

        $this->expectException(SpotNotFoundException::class);

        ($this->action)($dto);
    }
}
