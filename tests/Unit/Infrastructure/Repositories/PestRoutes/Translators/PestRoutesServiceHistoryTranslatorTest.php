<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\Enums\ServiceType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceHistoryTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceTypeTranslator;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Carbon\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\ServiceHistoryFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\ServiceTypeData;

class PestRoutesServiceHistoryTranslatorTest extends TestCase
{
    private PestRoutesServiceTypeTranslator|MockInterface $serviceTypeTranslatorMock;
    private PestRoutesServiceHistoryTranslator $serviceHistoryTranslator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceTypeTranslatorMock = \Mockery::mock(PestRoutesServiceTypeTranslator::class);

        $this->serviceHistoryTranslator = new PestRoutesServiceHistoryTranslator(
            $this->serviceTypeTranslatorMock
        );
    }

    /**
     * @test
     */
    public function it_translates_pest_routes_appointment_to_service_history(): void
    {
        $id = $this->faker->randomNumber(5);
        $customerId = $this->faker->randomNumber(5);
        $officeId = $this->faker->numberBetween(1, 100);
        $date = $this->faker->date();
        $checkIn = $date . '10:00:00';
        $checkOut = $date . '10:30:00';
        $duration = Carbon::parse($checkIn)->diffInSeconds(Carbon::parse($checkOut));
        $serviceType = ServiceTypeData::getTestDataOfTypes(ServiceTypeData::PRO)->first();

        /** @var PestRoutesAppointment $pestRoutesAppointment */
        $pestRoutesAppointment = AppointmentData::getTestData(1, [
            'appointmentID' => $id,
            'officeID' => $officeId,
            'customerID' => $customerId,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'dateCompleted' => $date,
        ])->first();

        $this->serviceTypeTranslatorMock
            ->shouldReceive('toDomain')
            ->with($serviceType)
            ->once()
            ->andReturn(ServiceType::REGULAR);

        $expected = ServiceHistoryFactory::make([
            'id' => $id,
            'officeId' => $officeId,
            'customerId' => $customerId,
            'serviceType' => ServiceType::REGULAR,
            'duration' => Duration::fromSeconds($duration),
            'date' => Carbon::instance($pestRoutesAppointment->checkIn),
        ]);

        $result = $this->serviceHistoryTranslator->toDomain($pestRoutesAppointment, $serviceType);

        $this->assertEquals($expected, $result);
    }
}
