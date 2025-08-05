<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesAppointmentsDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\TestValue;

class PestRoutesAppointmentsDataProcessorCacheWrapperTest extends TestCase
{
    private PestRoutesAppointmentsDataProcessorCacheWrapper $wrapper;
    private PestRoutesAppointmentsDataProcessor|MockInterface $wrappeeMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wrappeeMock = \Mockery::mock(PestRoutesAppointmentsDataProcessor::class);
        $this->wrapper = new PestRoutesAppointmentsDataProcessorCacheWrapper($this->wrappeeMock);
    }

    /**
     * @test
     */
    public function it_caches_extract_results(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $params = new SearchAppointmentsParams(
            officeIds: [TestValue::OFFICE_ID],
            routeIds: [TestValue::ROUTE_ID]
        );
        $appointments = AppointmentData::getTestData();

        $this->wrappeeMock->shouldReceive('extract')
            ->with($officeId, $params)
            ->once()
            ->andReturn($appointments);

        $result1 = $this->wrapper->extract($officeId, $params);
        $result2 = $this->wrapper->extract($officeId, $params);

        $this->assertSame($appointments, $result1);
        $this->assertSame($appointments, $result2);
    }

    /**
     * @test
     */
    public function it_does_not_cache_create_appointments(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $params = new CreateAppointmentsParams(
            customerId: TestValue::CUSTOMER_ID,
            typeId: TestValue::SERVICE_TYPE_ID,
        );

        $this->wrappeeMock->shouldReceive('create')
            ->with($officeId, $params)
            ->twice()
            ->andReturn(TestValue::APPOINTMENT_ID);

        $result1 = $this->wrapper->create($officeId, $params);
        $result2 = $this->wrapper->create($officeId, $params);

        $this->assertEquals(TestValue::APPOINTMENT_ID, $result1);
        $this->assertEquals(TestValue::APPOINTMENT_ID, $result2);
    }

    /**
     * @test
     */
    public function it_does_not_cache_update_appointments(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $params = new UpdateAppointmentsParams(
            appointmentId : TestValue::APPOINTMENT_ID,
        );

        $this->wrappeeMock->shouldReceive('update')
            ->with($officeId, $params)
            ->twice()
            ->andReturnTrue();

        $result1 = $this->wrapper->update($officeId, $params);
        $result2 = $this->wrapper->update($officeId, $params);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    /**
     * @test
     */
    public function it_does_not_cache_cancel_appointments(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $id = TestValue::APPOINTMENT_ID;

        $this->wrappeeMock->shouldReceive('cancel')
            ->twice()
            ->andReturn($id);

        $result1 = $this->wrapper->cancel($officeId, $id);
        $result2 = $this->wrapper->cancel($officeId, $id);

        $this->assertEquals(TestValue::APPOINTMENT_ID, $result1);
        $this->assertEquals(TestValue::APPOINTMENT_ID, $result2);
    }

    /**
     * @test
     */
    public function it_does_not_cache_assign_appointments(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $routeId = TestValue::ROUTE_ID;
        $appointmentId = TestValue::APPOINTMENT_ID;

        $this->wrappeeMock->shouldReceive('assignAppointment')
            ->twice()
            ->andReturnTrue();

        $result1 = $this->wrapper->assignAppointment($officeId, $routeId, $appointmentId);
        $result2 = $this->wrapper->assignAppointment($officeId, $routeId, $appointmentId);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }
}
