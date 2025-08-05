<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Exceptions\UpdateLockedAppointmentException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentsResource;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\OfficeData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesAppointmentsDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    private OfficesDataProcessor|MockInterface $officesDataProcessorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officesDataProcessorMock = \Mockery::mock(OfficesDataProcessor::class);
    }

    /**
     * @test
     */
    public function it_extracts_appointments(): void
    {
        $searchAppointmentsParams = new SearchAppointmentsParams(
            officeIds: [TestValue::OFFICE_ID],
            routeIds: [TestValue::ROUTE_ID]
        );
        $appointments = AppointmentData::getTestData(random_int(2, 5));
        $offices = OfficeData::getTestData(1, ['officeID' => TestValue::OFFICE_ID]);
        $officesCollection = new PestRoutesCollection($offices->all());
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(AppointmentsResource::class)
            ->callSequence('appointments', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (
                SearchAppointmentsParams $params,
                PestRoutesCollection $offices
            ) use ($searchAppointmentsParams, $officesCollection) {
                return $params === $searchAppointmentsParams
                    && $offices->getItems() === $officesCollection->getItems();
            })
            ->willReturn(new PestRoutesCollection($appointments->all()))
            ->mock();

        $this->officesDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($offices);

        $subject = new PestRoutesAppointmentsDataProcessor(
            $pestRoutesClientMock,
            $this->officesDataProcessorMock
        );

        $result = $subject->extract(TestValue::OFFICE_ID, $searchAppointmentsParams);

        $this->assertEquals($appointments, $result);
    }

    /**
     * @test
     */
    public function it_assigns_appointment_to_spot(): void
    {
        $client = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(AppointmentsResource::class)
            ->callSequence('appointments', 'update')
            ->methodExpectsArgs('update', function (UpdateAppointmentsParams $params) {
                $array = $params->toArray();

                return $array['appointmentID'] === TestValue::APPOINTMENT_ID
                    && $array['spotID'] === TestValue::SPOT_ID
                    && $array['routeID'] === TestValue::ROUTE_ID
                    && $array['officeID'] === TestValue::OFFICE_ID;
            })
            ->willReturn(TestValue::APPOINTMENT_ID)
            ->mock();

        $subject = new PestRoutesAppointmentsDataProcessor(
            $client,
            $this->officesDataProcessorMock
        );

        $result = $subject->assignAppointment(
            TestValue::OFFICE_ID,
            TestValue::ROUTE_ID,
            TestValue::APPOINTMENT_ID,
            TestValue::SPOT_ID
        );

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_updating_appointment(): void
    {
        $pestRoutesApiErrorMessage = 'Response unsuccessful: Appointment is locked and is not allowed to change spotId, unlock it first';
        $updateAppointmentsParamsMock = \Mockery::mock(UpdateAppointmentsParams::class);
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(AppointmentsResource::class)
            ->callSequence('appointments', 'update')
            ->methodExpectsArgs('update', function (UpdateAppointmentsParams $params) {
                $array = $params->toArray();

                return $array['appointmentID'] === TestValue::APPOINTMENT_ID
                    && $array['spotID'] === TestValue::SPOT_ID
                    && $array['routeID'] === TestValue::ROUTE_ID
                    && $array['officeID'] === TestValue::OFFICE_ID;
            })
            ->willThrow(new \Exception($pestRoutesApiErrorMessage))
            ->mock();

        $subject = new PestRoutesAppointmentsDataProcessor(
            $pestRoutesClientMock,
            $this->officesDataProcessorMock
        );

        $this->expectException(UpdateLockedAppointmentException::class);

        $subject->update(TestValue::OFFICE_ID, $updateAppointmentsParamsMock);
    }

    /**
    * @test
    */
    public function it_updates_appointments(): void
    {
        $updateAppointmentsParamsMock = \Mockery::mock(UpdateAppointmentsParams::class);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(AppointmentsResource::class)
            ->callSequence('appointments', 'update')
            ->methodExpectsArgs('update', [$updateAppointmentsParamsMock])
            ->willReturn(true)
            ->mock();

        $subject = new PestRoutesAppointmentsDataProcessor(
            $pestRoutesClientMock,
            $this->officesDataProcessorMock
        );

        $result = $subject->update(TestValue::OFFICE_ID, $updateAppointmentsParamsMock);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_creates_appointment(): void
    {
        $createAppointmentsParams = new CreateAppointmentsParams(
            customerId: TestValue::CUSTOMER_ID,
            typeId: TestValue::SERVICE_TYPE_ID,
        );

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(AppointmentsResource::class)
            ->callSequence('appointments', 'create')
            ->methodExpectsArgs(
                'create',
                fn (CreateAppointmentsParams $params) => $params === $createAppointmentsParams
            )
            ->willReturn(TestValue::APPOINTMENT_ID)
            ->mock();

        $subject = new PestRoutesAppointmentsDataProcessor(
            $pestRoutesClientMock,
            $this->officesDataProcessorMock
        );

        $result = $subject->create(TestValue::OFFICE_ID, $createAppointmentsParams);

        $this->assertEquals(TestValue::APPOINTMENT_ID, $result);
    }

    /**
     * @test
     */
    public function it_cancels_appointment(): void
    {
        $reason = $this->faker->text(16);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(AppointmentsResource::class)
            ->callSequence('appointments', 'cancel')
            ->methodExpectsArgs(
                'cancel',
                fn (int $id, string $cancelReason, int $cancelledBy) => $id === TestValue::APPOINTMENT_ID
                    && $cancelReason === $reason
                    && $cancelledBy === TestValue::IVR_SCHEDULER_ID
            )
            ->willReturn(TestValue::APPOINTMENT_ID)
            ->mock();

        $subject = new PestRoutesAppointmentsDataProcessor(
            $pestRoutesClientMock,
            $this->officesDataProcessorMock
        );

        $result = $subject->cancel(
            TestValue::OFFICE_ID,
            TestValue::APPOINTMENT_ID,
            $reason,
            TestValue::IVR_SCHEDULER_ID
        );

        $this->assertEquals(TestValue::APPOINTMENT_ID, $result);
    }
}
