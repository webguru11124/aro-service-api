<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesSchedulingAppointmentTranslator;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\CustomerData;
use Tests\Tools\PestRoutesData\ServiceTypeData;
use Tests\Tools\TestValue;

class PestRoutesSchedulingAppointmentTranslatorTest extends TestCase
{
    private PestRoutesSchedulingAppointmentTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = new PestRoutesSchedulingAppointmentTranslator(
            app(PestRoutesCustomerTranslator::class)
        );
    }

    /**
     * @test
     */
    public function it_translates_appointment_from_pest_routes_data_to_domain(): void
    {
        /** @var PestRoutesCustomer $customer */
        $customer = CustomerData::getTestData(1, ['officeID' => TestValue::OFFICE_ID])->first();
        /** @var PestRoutesServiceType $serviceType */
        $serviceType = ServiceTypeData::getTestDataOfTypes(ServiceTypeData::INITIAL)->first();

        /** @var PestRoutesAppointment $appointment */
        $appointment = AppointmentData::getTestData(1, [
            'officeID' => TestValue::OFFICE_ID,
            'customerID' => $customer->id,
            'type' => $serviceType->id,
            'duration' => 30,
            'date' => Carbon::today(TestValue::TIME_ZONE)->toDateString(),
            'dateCompleted' => Carbon::today(TestValue::TIME_ZONE)->toDateString(),
            'status' => AppointmentStatus::Completed->value,
        ])->first();

        $result = $this->translator->toDomain($appointment, $serviceType, $customer);

        $this->assertEquals($appointment->id, $result->getId());
        $this->assertEquals(new Coordinate($customer->latitude, $customer->longitude), $result->getLocation());
        $this->assertEquals(Carbon::instance($appointment->start), $result->getDate());
        $this->assertEquals(Carbon::instance($appointment->dateCompleted), $result->getDateCompleted());
        $this->assertEquals(Duration::fromMinutes($appointment->duration), $result->getDuration());
    }
}
