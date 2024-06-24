<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesAppointmentTranslator;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\AppointmentReminderData;
use Tests\Tools\PestRoutesData\CustomerData;
use Tests\Tools\PestRoutesData\ServiceTypeData;
use Tests\Tools\PestRoutesData\SpotData;

class PestRoutesAppointmentTranslatorTest extends TestCase
{
    private const DATE = '2023-02-03';
    private const START_TIME = '08:00:00';
    private const END_TIME = '08:30:00';

    private PestRoutesAppointmentTranslator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestRoutesAppointmentTranslator();
    }

    /**
     * @test
     */
    public function it_translates_appointment_from_pest_routes_to_domain(): void
    {
        $officeId = $this->faker->randomNumber(2);

        /** @var PestRoutesCustomer $customer */
        $customer = CustomerData::getTestData(1, ['officeID' => $officeId])->first();
        /** @var PestRoutesServiceType $serviceType */
        $serviceType = ServiceTypeData::getTestDataOfTypes(ServiceTypeData::INITIAL)->first();

        $substitution = [
            'officeID' => $officeId,
            'customerID' => $customer->id,
            'type' => $serviceType->id,
        ];
        /** @var PestRoutesAppointment $appointment */
        $appointment = AppointmentData::getTestData(1, $substitution)->first();
        $reminders = AppointmentReminderData::getTestData(1);

        /** @var Spot $spot */
        $spot = SpotData::getTestData()->first();

        /** @var Appointment $result */
        $result = $this->subject->toDomain($appointment, $customer, $serviceType, $spot, $reminders);

        $this->assertTrue($result->isNotified());
        $this->assertEquals($appointment->id, $result->getId());

        $expectedLocation = new Coordinate($customer->latitude, $customer->longitude);
        $this->assertEquals($expectedLocation, $result->getLocation());

        $expectedDescription = $serviceType->description;
        $this->assertEquals($expectedDescription, $result->getDescription());

        $expectedSkills = new Collection([
            Skill::tryFromState($customer->address->state),
            new Skill(Skill::INITIAL_SERVICE),
        ]);
        $this->assertEquals($expectedSkills, $result->getSkills());
        $this->assertEquals($appointment->officeId, $result->getOfficeId());
        $this->assertEquals($appointment->customerId, $result->getCustomerId());
        $this->assertEquals(Carbon::instance($spot->start), $result->getTimeWindow()->getStartAt());
        $this->assertEquals(Carbon::instance($spot->end), $result->getTimeWindow()->getEndAt());
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @test
     */
    public function it_translates_appointment_from_pest_routes_to_domain_when_customer_has_no_state(
        string $customerState,
        string $billingState,
        Collection $expectedSkills,
    ): void {
        $officeId = $this->faker->randomNumber(2);

        /** @var PestRoutesCustomer $customer */
        $customer = CustomerData::getTestData(1, ['officeID' => $officeId, 'state' => $customerState, 'billingState' => $billingState])->first();
        /** @var PestRoutesServiceType $serviceType */
        $serviceType = ServiceTypeData::getTestDataOfTypes(ServiceTypeData::INITIAL)->first();

        $substitution = [
            'officeID' => $officeId,
            'customerID' => $customer->id,
            'type' => $serviceType->id,
        ];
        /** @var PestRoutesAppointment $appointment */
        $appointment = AppointmentData::getTestData(1, $substitution)->first();
        $reminders = AppointmentReminderData::getTestData(1);

        /** @var Spot $spot */
        $spot = SpotData::getTestData()->first();

        /** @var Appointment $result */
        $result = $this->subject->toDomain($appointment, $customer, $serviceType, $spot, $reminders);

        $this->assertEquals($expectedSkills, $result->getSkills());

        if (!$customerState && !$billingState) {
            Log::shouldReceive('notice');
        }
    }

    /**
     * Data provider for translating appointments.
     */
    public static function stateDataProvider(): array
    {
        return [
            'customer has no state composed' => [
                'customerState' => '',
                'billingState' => '',
                'expectedSkills' => new Collection([new Skill(Skill::INITIAL_SERVICE)]),
            ],
            'customer address has no state' => [
                'customerState' => '',
                'billingState' => 'ID',
                'expectedSkills' => new Collection([
                    Skill::tryFromState('ID'),
                    new Skill(Skill::INITIAL_SERVICE),
                ]),
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider timeWindowProvider
     */
    public function it_hydrates_appointments_with_the_correct_expected_time_window(
        $pestroutesAppointmentStart,
        $pestroutesAppointmentEnd,
        $pestroutesAppointmentDate,
        $pestroutesTimeWindow,
        $expectedArrival
    ) {
        $substitution = [
            'date' => $pestroutesAppointmentDate,
            'start' => $pestroutesAppointmentStart,
            'end' => $pestroutesAppointmentEnd,
            'timeWindow' => $pestroutesTimeWindow, // Pestroutes time window is a slightly different concept from our Time Window
        ];

        /** @var PestRoutesAppointment $appointment */
        $appointment = AppointmentData::getTestData(1, $substitution)->first();
        /** @var PestRoutesCustomer $customer */
        $customer = CustomerData::getTestData(1)->first();
        /** @var PestRoutesServiceType $serviceType */
        $serviceType = ServiceTypeData::getTestDataOfTypes(ServiceTypeData::INITIAL)->first();
        $reminders = new Collection();
        $spot = SpotData::getTestData()->first();

        $appointment = $this->subject->toDomain($appointment, $customer, $serviceType, $spot, $reminders);

        $expectedStartAt = $expectedArrival->getStartAt()->toDateTimeString();
        $actualStartAt = $appointment->getExpectedArrival()->getStartAt()->toDateTimeString();
        $expectedEndAt = $expectedArrival->getEndAt()->toDateTimeString();
        $actualEndAt = $appointment->getExpectedArrival()->getEndAt()->toDateTimeString();

        $this->assertEquals($expectedStartAt, $actualStartAt, "Expected Start: $expectedStartAt, Actual Start: $actualStartAt");
        $this->assertEquals($expectedEndAt, $actualEndAt, "Expected End: $expectedEndAt, Actual End: $actualEndAt");
    }

    public static function timeWindowProvider(): array
    {
        $date = '2023-02-03';

        return [
            'pestroutes_time_window_AM' => [
                'pestroutesAppointmentStart' => self::START_TIME,
                'pestroutesAppointmentEnd' => self::END_TIME,
                'pestroutesAppointmentDate' => $date,
                'pestroutesTimeWindow' => 'AM',
                'expectedArrival' => new TimeWindow(
                    Carbon::parse($date)->startOfDay(),
                    Carbon::parse($date)->midDay()
                ),
            ],
            'pestroutes_time_window_PM' => [
                'pestroutesAppointmentStart' => self::START_TIME,
                'pestroutesAppointmentEnd' => self::END_TIME,
                'pestroutesAppointmentDate' => $date,
                'pestroutesTimeWindow' => 'PM',
                'expectedArrival' => new TimeWindow(
                    Carbon::parse($date)->midDay(),
                    Carbon::parse($date)->endOfDay()
                ),
            ],
            'pestroutes_time_window_AT' => [
                'pestroutesAppointmentStart' => self::START_TIME,
                'pestroutesAppointmentEnd' => self::END_TIME,
                'pestroutesAppointmentDate' => $date,
                'pestroutesTimeWindow' => 'AT',
                'expectedArrival' => new TimeWindow(
                    Carbon::parse($date)->startOfDay(),
                    Carbon::parse($date)->endOfDay()
                ),
            ],
            'pestroutes_time_window_Timed' => [
                'pestroutesAppointmentStart' => self::START_TIME,
                'pestroutesAppointmentEnd' => self::END_TIME,
                'pestroutesAppointmentDate' => $date,
                'pestroutesTimeWindow' => 'Timed',
                'expectedArrival' => new TimeWindow( // Should be the start and end times of the appointment
                    Carbon::parse("$date " . self::START_TIME),
                    Carbon::parse("$date " . self::END_TIME)
                ),
            ],
            'pestroutes_time_window_null' => [ // Default to Anytime
                'pestroutesAppointmentStart' => self::START_TIME,
                'pestroutesAppointmentEnd' => self::END_TIME,
                'pestroutesAppointmentDate' => $date,
                'pestroutesTimeWindow' => null,
                'expectedArrival' => new TimeWindow( // Treat it like an anytime appointment
                    Carbon::parse($date)->startOfDay(),
                    Carbon::parse($date)->endOfDay()
                ),
            ],
            'pestroutes_time_window_timed_with_invalid_end' => [
                'pestroutesAppointmentStart' => self::START_TIME,
                'pestroutesAppointmentEnd' => '00:00:00',
                'pestroutesAppointmentDate' => $date,
                'pestroutesTimeWindow' => 'Timed',
                'expectedArrival' => new TimeWindow(
                    Carbon::parse("$date " . self::START_TIME),
                    Carbon::parse($date)->endOfDay()
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_hydrates_appointments_with_the_correct_expected_time_window_from_appointment(): void
    {
        $date = self::DATE;
        $substitution = [
            'date' => $date,
            'start' => self::START_TIME,
            'end' => self::END_TIME,
            'timeWindow' => 'AT',
        ];

        /** @var PestRoutesAppointment $appointment */
        $appointment = AppointmentData::getTestData(1, $substitution)->first();
        /** @var PestRoutesCustomer $customer */
        $customer = CustomerData::getTestData(1)->first();
        /** @var PestRoutesServiceType $serviceType */
        $serviceType = ServiceTypeData::getTestDataOfTypes(ServiceTypeData::PRO)->first();
        $expectedTimeWindow = new TimeWindow(
            Carbon::parse("$date " . self::START_TIME, $appointment->start->getTimezone()),
            Carbon::parse("$date " . self::END_TIME, $appointment->end->getTimezone())
        );

        $appointment = $this->subject->toDomain($appointment, $customer, $serviceType, null, new Collection());

        $this->assertEquals($expectedTimeWindow->getStartAt(), $appointment->getTimeWindow()->getStartAt());
    }
}
