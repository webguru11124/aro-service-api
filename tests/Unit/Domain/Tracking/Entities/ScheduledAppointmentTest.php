<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Entities;

use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\Tracking\Entities\ScheduledAppointment;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\Tracking\CustomerFactory;
use Tests\Tools\TestValue;

class ScheduledAppointmentTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $appointment = new ScheduledAppointment(
            id: 1,
            date: Carbon::today(),
            serviceTimeWindow: new TimeWindow(Carbon::today()->setHour(9), Carbon::today()->setHour(10)),
            expectedTimeWindow: new TimeWindow(Carbon::today()->setHour(10), Carbon::today()->setHour(11)),
            dateComplete: Carbon::today(),
            customer: CustomerFactory::make(['id' => TestValue::CUSTOMER_ID])
        );

        $this->assertEquals(1, $appointment->getId());
        $this->assertEquals(Carbon::today()->toDateString(), $appointment->getDate()->toDateString());
        $this->assertEquals(Carbon::today()->setHour(9)->toDateString(), $appointment->getServiceTimeWindow()->getStartAt()->toDateString());
        $this->assertEquals(Carbon::today()->setHour(10)->toDateString(), $appointment->getServiceTimeWindow()->getEndAt()->toDateString());
        $this->assertEquals(Carbon::today()->setHour(10)->toDateString(), $appointment->getExpectedTimeWindow()->getStartAt()->toDateString());
        $this->assertEquals(Carbon::today()->setHour(11)->toDateString(), $appointment->getExpectedTimeWindow()->getEndAt()->toDateString());
        $this->assertEquals(Carbon::today()->toDateString(), $appointment->getDateComplete()->toDateString());
        $this->assertEquals(TestValue::CUSTOMER_ID, $appointment->getCustomer()->getId());
    }
}
