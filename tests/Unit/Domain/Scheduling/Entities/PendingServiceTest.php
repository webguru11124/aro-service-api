<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\Scheduling\Entities\Customer;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\Plan;
use App\Domain\Scheduling\ValueObjects\CustomerPreferences;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Scheduling\AppointmentFactory;
use Tests\Tools\Factories\Scheduling\PlanFactory;

class PendingServiceTest extends TestCase
{
    private int $subscriptionId;
    private Plan $plan;
    private Customer $customer;
    private Appointment $previousAppointment;
    private Appointment $nextAppointment;
    private CarbonInterface $nextServiceDate;
    private string $preferredStart;
    private string $preferredEnd;
    private int $preferredEmployeeId;
    private int $preferredDay;

    private const BASIC_ID = 4;
    private const BASIC = 'Basic';
    private const BASIC_PEST_ROUTES_ID = 1799;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionId = $this->faker->randomNumber(3);

        $this->plan = PlanFactory::make([
            'id' => self::BASIC_ID,
            'name' => self::BASIC,
            'serviceTypeId' => self::BASIC_PEST_ROUTES_ID,
        ]);

        $this->customer = new Customer(
            id: $this->faker->randomNumber(3),
            name: $this->faker->name,
            location: new Coordinate($this->faker->latitude, $this->faker->longitude),
            email: $this->faker->email(),
            preferredTechId: $this->faker->randomNumber(3),
        );

        $this->previousAppointment = AppointmentFactory::make([
            'initial' => true,
            'date' => Carbon::instance($this->faker->dateTimeThisYear()),
            'dateCompleted' => Carbon::instance($this->faker->dateTimeThisYear()),
        ]);

        $this->nextAppointment = AppointmentFactory::make([
            'date' => Carbon::tomorrow(),
        ]);

        $this->nextServiceDate = Carbon::today();
        $this->preferredStart = $this->faker->time;
        $this->preferredEnd = $this->faker->time;
        $this->preferredEmployeeId = $this->faker->randomNumber(3);
        $this->preferredDay = $this->faker->randomNumber(2);
    }

    /**
     * @test
     */
    public function it_returns_correct_values(): void
    {
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: $this->previousAppointment,
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(
                preferredStart: $this->preferredStart,
                preferredEnd: $this->preferredEnd,
                preferredEmployeeId: $this->preferredEmployeeId,
                preferredDay: $this->preferredDay,
            ),
            nextAppointment: $this->nextAppointment,
        );

        $this->assertEquals($this->subscriptionId, $pendingService->getSubscriptionId());
        $this->assertEquals($this->plan, $pendingService->getPlan());
        $this->assertEquals($this->customer, $pendingService->getCustomer());
        $this->assertEquals($this->previousAppointment, $pendingService->getPreviousAppointment());
        $this->assertEquals($this->preferredStart, $pendingService->getPreferredStart());
        $this->assertEquals($this->preferredEnd, $pendingService->getPreferredEnd());
        $this->assertEquals($this->preferredEmployeeId, $pendingService->getPreferredEmployeeId());
        $this->assertEquals($this->preferredDay, $pendingService->getPreferredDay());
        $this->assertEquals($this->plan->getServiceTypeId(), $pendingService->getServiceTypeId());
        $this->assertEquals($this->customer->getLocation(), $pendingService->getLocation());
        $this->assertEquals($this->nextAppointment, $pendingService->getNextAppointment());
        $this->assertTrue($pendingService->isRescheduled());
    }

    /**
     * @test
     *
     * ::getPreferredStart
     * ::getPreferredEnd
     */
    public function it_returns_default_preferred_time(): void
    {
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: $this->previousAppointment,
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(),
        );

        $this->assertEquals('08:00:00', $pendingService->getPreferredStart());
        $this->assertEquals('20:00:00', $pendingService->getPreferredEnd());
    }

    /**
     * @test
     *
     * ::resetPreferredEmployeeId
     * ::resetCustomerPreferredTechId
     */
    public function it_resets_preferred_employee_id_and_customer_preferred_tech_id(): void
    {
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: $this->previousAppointment,
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(),
        );

        $pendingService->resetPreferredEmployeeId();

        $this->assertNull($pendingService->getPreferredEmployeeId());
        $this->assertNull($pendingService->getCustomer()->getPreferredTechId());
    }

    /**
     * @test
     *
     * ::isHighPriority
     */
    public function it_returns_true_when_service_is_high_priority(): void
    {
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: AppointmentFactory::make([
                'initial' => true,
                'date' => Carbon::today()->subDays(80),
                'dateCompleted' => Carbon::today()->subDays(80),
            ]),
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(),
        );

        $this->assertTrue($pendingService->isHighPriority());
    }

    /**
     * @test
     *
     * ::getNextServiceTimeWindow
     */
    public function it_returns_next_service_time_window_for_initial_followup(): void
    {
        $preServiceDate = Carbon::today()->subDays(10);
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: AppointmentFactory::make([
                'initial' => true,
                'date' => $preServiceDate,
                'dateCompleted' => $preServiceDate,
            ]),
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(),
        );

        $expectedStartAt = $preServiceDate->clone()->addDays($this->plan->getInitialFollowupDays());
        $expectedEndAt = $expectedStartAt->clone()->addDays(2);

        $this->assertEquals($expectedStartAt->toDateString(), $pendingService->getNextServiceTimeWindow()->getStartAt()->toDateString());
        $this->assertEquals($expectedEndAt->toDateString(), $pendingService->getNextServiceTimeWindow()->getEndAt()->toDateString());
    }

    /**
     * @test
     *
     * ::getNextServiceTimeWindow
     */
    public function it_returns_next_service_time_window_for_regular_service(): void
    {
        $preServiceDate = Carbon::today()->subDays(40);
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: AppointmentFactory::make([
                'initial' => false,
                'date' => $preServiceDate,
                'dateCompleted' => $preServiceDate,
            ]),
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(),
        );

        $expectedStartAt = $preServiceDate->clone()->addDays($this->plan->getServiceIntervalDays($preServiceDate));
        $expectedEndAt = $expectedStartAt->clone()->addDays($this->plan->getServicePeriodDays($preServiceDate));

        $this->assertEquals($expectedStartAt->toDateString(), $pendingService->getNextServiceTimeWindow()->getStartAt()->toDateString());
        $this->assertEquals($expectedEndAt->toDateString(), $pendingService->getNextServiceTimeWindow()->getEndAt()->toDateString());
    }

    /**
     * @test
     *
     * ::getPriority
     */
    public function it_returns_max_priority_for_high_priority_service(): void
    {
        $preServiceDate = Carbon::today()->subDays(100);
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: AppointmentFactory::make([
                'initial' => true,
                'date' => $preServiceDate,
                'dateCompleted' => $preServiceDate,
            ]),
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(),
        );

        $this->assertEquals(100, $pendingService->getPriority());
    }

    /**
     * @test
     *
     * ::getPriority
     */
    public function it_returns_min_priority(): void
    {
        $preServiceDate = Carbon::today()->subDays(10);
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: AppointmentFactory::make([
                'initial' => false,
                'date' => $preServiceDate,
            ]),
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(),
        );

        $this->assertEquals(1, $pendingService->getPriority());
    }

    /**
     * @test
     *
     * ::getPriority
     */
    public function it_returns_priority_based_on_next_service_due_date(): void
    {
        $preServiceDate = Carbon::today()->subDays(50);
        $pendingService = new PendingService(
            subscriptionId: $this->subscriptionId,
            plan: $this->plan,
            customer: $this->customer,
            previousAppointment: AppointmentFactory::make([
                'initial' => false,
                'date' => $preServiceDate,
                'dateCompleted' => $preServiceDate,
            ]),
            nextServiceDate: $this->nextServiceDate,
            customerPreferences: new CustomerPreferences(),
        );

        $this->assertGreaterThanOrEqual(67, $pendingService->getPriority());
    }
}
