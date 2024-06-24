<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\TestValue;
use Tests\Traits\DomainDataAndObjects;
use App\Domain\RouteOptimization\ValueObjects\ServiceDuration;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;

class AppointmentTest extends TestCase
{
    use DomainDataAndObjects;
    use CloneWorkEvent;

    private const DURATION_MIN = 61;
    private const INITIAL_APPOINTMENT_PRIORITY = 100;
    private const RESERVICE_APPOINTMENT_PRIORITY = 95;
    private const HALF_DAY_APPOINTMENT_PRIORITY = 80;
    private const NOTIFIED_APPOINTMENT_PRIORITY = 70;
    private const DEFAULT_APPOINTMENT_PRIORITY = 10;
    private const INITIAL_APPOINTMENT_DURATION = 40;
    private const INITIAL_SETUP_DURATION = 8;
    private const STARTING_HOUR = 8;
    private const TEST_WEIGHTED_INITIAL = 2;
    private const TEST_WEIGHTED_REGULAR = 1;

    /**
     * @test
     */
    public function create_appointment(): void
    {
        $now = Carbon::now();

        $ago = clone($now);
        $ago->sub(sprintf('%d minutes', self::DURATION_MIN));

        $location = new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE);

        $skills = [
            new Skill(Skill::GA),
            new Skill(Skill::INITIAL_SERVICE),
        ];

        $officeId = $this->faker->randomNumber(2);
        $customerId = $this->faker->randomNumber(5);
        $preferredTechId = $this->faker->randomNumber(6);
        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            self::APPOINTMENT_BASIC_LABEL,
            $location,
            true,
            $officeId,
            $customerId,
            $preferredTechId,
            collect($skills),
        );

        $this->assertEquals($location, $appointment->getLocation());
        $this->assertEquals($skills, $appointment->getSkills()->all());
        $this->assertEquals(self::APPOINTMENT_BASIC_LABEL, $appointment->getDescription());
        $this->assertEquals(TestValue::APPOINTMENT_ID, $appointment->getId());
        $this->assertTrue($appointment->isNotified());
    }

    /**
     * @test
     *
     * @dataProvider appointmentPriorityProvider
     *
     * ::getPriority
     */
    public function it_gets_priorities(
        string $description,
        string $startAt,
        string $endAt,
        bool $notified,
        int $expectedPriority
    ): void {
        Config::set('aptive.appointment_priority.initial', self::INITIAL_APPOINTMENT_PRIORITY);
        Config::set('aptive.appointment_priority.reservice', self::RESERVICE_APPOINTMENT_PRIORITY);
        Config::set('aptive.appointment_priority.half_day', self::HALF_DAY_APPOINTMENT_PRIORITY);
        Config::set('aptive.appointment_priority.notified', self::NOTIFIED_APPOINTMENT_PRIORITY);
        Config::set('aptive.appointment_priority.default', self::DEFAULT_APPOINTMENT_PRIORITY);

        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            $description,
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            $notified,
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(6),
            collect([new Skill(Skill::GA), new Skill(Skill::INITIAL_SERVICE)]),
        );
        $appointment->setExpectedArrival(
            new TimeWindow(
                Carbon::parse($startAt),
                Carbon::parse($endAt),
            )
        );

        $result = $appointment->getPriority();

        $this->assertEquals($expectedPriority, $result);
    }

    public static function appointmentPriorityProvider(): iterable
    {
        $date = '2023-08-11';
        $startAt = '08:00:00';
        $endAt = '20:00:00';
        $midDay = '12:00:00';
        yield [
            self::APPOINTMENT_BASIC_LABEL,
            $date . ' ' . $startAt,
            $date . ' ' . $midDay,
            false,
            self::HALF_DAY_APPOINTMENT_PRIORITY,
        ];
        yield [
            self::APPOINTMENT_BASIC_LABEL,
            $date . ' ' . $midDay,
            $date . ' ' . $endAt,
            false,
            self::HALF_DAY_APPOINTMENT_PRIORITY,
        ];
        yield [
            self::APPOINTMENT_BASIC_LABEL,
            $date . ' ' . $startAt,
            $date . ' ' . $endAt,
            false,
            self::DEFAULT_APPOINTMENT_PRIORITY,
        ];
        yield [
            self::APPOINTMENT_BASIC_LABEL,
            $date . ' ' . $startAt,
            $date . ' ' . $endAt,
            true,
            self::NOTIFIED_APPOINTMENT_PRIORITY,
        ];
        yield [
            'Initial',
            $date . ' ' . $startAt,
            $date . ' ' . $endAt,
            false,
            self::INITIAL_APPOINTMENT_PRIORITY,
        ];
        yield [
            'Reservice',
            $date . ' ' . $startAt,
            $date . ' ' . $endAt,
            false,
            self::RESERVICE_APPOINTMENT_PRIORITY,
        ];
    }

    /**
     * @test
     *
     * ::isNotified
     */
    public function it_returns_true_when_appointment_has_notification(): void
    {
        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            true,
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(6),
            collect([new Skill(Skill::GA), new Skill(Skill::INITIAL_SERVICE)]),
        );

        $this->assertTrue($appointment->isNotified());
    }

    /**
     * @test
     *
     * ::isInitial
     */
    public function it_returns_true_when_appointment_is_initial(): void
    {
        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            'Initial',
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            false,
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(6),
            collect([new Skill(Skill::GA), new Skill(Skill::INITIAL_SERVICE)]),
        );

        $this->assertTrue($appointment->isInitial());
    }

    /**
     * @test
     *
     * ::isReservice
     */
    public function it_returns_true_when_appointment_is_reservice(): void
    {
        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            'Reservice',
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            false,
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(6),
            collect([new Skill(Skill::GA), new Skill(Skill::INITIAL_SERVICE)]),
        );

        $this->assertTrue($appointment->isReservice());
    }

    /**
     * @test
     *
     * ::isHalfDay
     */
    public function it_returns_false_when_appointment_expected_arrival_window_is_not_set(): void
    {
        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            false,
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(6),
            collect([new Skill(Skill::GA), new Skill(Skill::INITIAL_SERVICE)]),
        );

        $this->assertFalse($appointment->isHalfDay());
    }

    /**
     * @test
     *
     * @dataProvider appointmentExpectedArrivalWindowProvider
     *
     * ::isHalfDay
     */
    public function it_validates_appointment_expected_arrival_window_is_half_day(
        string $startAt,
        string $endAt,
        bool $expectedResult
    ): void {
        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            false,
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(6),
            collect([new Skill(Skill::GA), new Skill(Skill::INITIAL_SERVICE)]),
        );
        $appointment->setExpectedArrival(
            new TimeWindow(
                Carbon::parse($startAt),
                Carbon::parse($endAt),
            )
        );

        $this->assertEquals($expectedResult, $appointment->isHalfDay());
    }

    /**
     * @test
     *
     * @dataProvider weightDataProvider
     */
    public function it_returns_correct_weight_based_on_appointment_description(string $description, int $expectedWeight): void
    {
        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            $description,
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            false,
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(6),
            collect([new Skill(Skill::GA), new Skill(Skill::INITIAL_SERVICE)]),
        );

        $this->assertEquals($expectedWeight, $appointment->getWeight());
    }

    /**
     * @test
     */
    public function it_resolves_service_duration_correctly(): void
    {
        $landSqFt = 10000.0;
        $buildingSqFt = 5000.0;
        $historicalAverageDuration = 20;

        $propertyDetailsMock = $this->createMock(PropertyDetails::class);
        $propertyDetailsMock->method('getLandSqFt')->willReturn($landSqFt);
        $propertyDetailsMock->method('getBuildingSqFt')->willReturn($buildingSqFt);

        $appointment = new Appointment(
            TestValue::APPOINTMENT_ID,
            'Test Appointment',
            new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID
        );

        $serviceDuration = new ServiceDuration($propertyDetailsMock, $historicalAverageDuration);

        $appointment->resolveServiceDuration($propertyDetailsMock, $historicalAverageDuration, null);

        $this->assertEquals($serviceDuration->getMinimumDuration(), $appointment->getMinimumDuration());
        $this->assertEquals($serviceDuration->getMaximumDuration(), $appointment->getMaximumDuration());
        $this->assertEquals($serviceDuration->getOptimumDuration(), $appointment->getDuration());
    }

    public static function weightDataProvider(): iterable
    {
        yield [
            'Initial',
            self::TEST_WEIGHTED_INITIAL,
        ];

        yield [
            'Reservice',
            self::TEST_WEIGHTED_REGULAR,
        ];

        yield [
            'Regular appointment',
            self::TEST_WEIGHTED_REGULAR,
        ];
    }

    public static function appointmentExpectedArrivalWindowProvider(): iterable
    {
        $date = '2023-08-11';
        $startAt = '08:00:00';
        $endAt = '20:00:00';
        $midDay = '12:00:00';
        yield [
            $date . ' ' . $startAt,
            $date . ' ' . $midDay,
            true,
        ];
        yield [
            $date . ' ' . $midDay,
            $date . ' ' . $endAt,
            true,
        ];
        yield [
            $date . ' ' . $startAt,
            $date . ' ' . $endAt,
            false,
        ];
    }

    /**
     * @test
     *
     * @dataProvider durationDataProvider
     */
    public function it_returns_proper_setup_duration(Appointment $appointment, Duration $expectedDuration): void
    {
        $result = $appointment->getSetupDuration();

        $this->assertEquals($expectedDuration, $result);
    }

    public static function durationDataProvider(): iterable
    {
        yield [
            AppointmentFactory::make(['description' => 'regular appointment']),
            Duration::fromMinutes(3),
        ];
        yield [
            AppointmentFactory::make(['description' => 'reservice appointment']),
            Duration::fromMinutes(3),
        ];
        yield [
            AppointmentFactory::make(['description' => 'initial appointment']),
            Duration::fromMinutes(5),
        ];
    }

    /**
     * @test
     */
    public function set_start_at_adjusting_end_at(): void
    {
        Config::set('aptive.default_appointment_duration', self::INITIAL_APPOINTMENT_DURATION);
        Config::set('aptive.appointment_setup_time.initial', self::INITIAL_SETUP_DURATION);
        Config::set('aptive.starting_hour', self::STARTING_HOUR);

        /** @var Appointment $appointment */
        $appointment = AppointmentFactory::make(['description' => 'initial']);
        $startAt = (new Carbon('2024-03-19', TestValue::TIME_ZONE))->setHour(self::STARTING_HOUR);
        $appointment->setStartAtAndAdjustEndAt($startAt);

        $expectedEndAt = (new Carbon('2024-03-19', TestValue::TIME_ZONE))
            ->setHour(self::STARTING_HOUR)
            ->addMinutes(self::INITIAL_APPOINTMENT_DURATION + self::INITIAL_SETUP_DURATION);

        $this->assertEquals($expectedEndAt, $appointment->getTimeWindow()->getEndAt());
    }

    /**
     * @test
     */
    public function get_total_service_minutes(): void
    {
        Config::set('aptive.default_appointment_duration', self::INITIAL_APPOINTMENT_DURATION);
        Config::set('aptive.appointment_setup_time.initial', self::INITIAL_SETUP_DURATION);
        Config::set('aptive.starting_hour', self::STARTING_HOUR);

        /** @var Appointment $appointment */
        $appointment = AppointmentFactory::make(['description' => 'initial']);

        $totalMinutes = self::INITIAL_APPOINTMENT_DURATION + self::INITIAL_SETUP_DURATION;

        $this->assertEquals($totalMinutes, $appointment->getTotalServiceTime()->getTotalMinutes());
    }

    private function getSubject(): WorkEvent
    {
        return AppointmentFactory::make();
    }
}
