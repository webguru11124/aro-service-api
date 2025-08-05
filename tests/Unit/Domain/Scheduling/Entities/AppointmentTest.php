<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\Scheduling\CustomerFactory;
use Tests\Tools\TestValue;

class AppointmentTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $appointment = new Appointment(
            id: 1,
            initial: true,
            date: Carbon::today(),
            dateCompleted: Carbon::today(),
            customer: CustomerFactory::make([
                'id' => TestValue::CUSTOMER_ID,
                'location' => new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
            ]),
            duration: Duration::fromMinutes(30),
        );

        $this->assertEquals(1, $appointment->getId());
        $this->assertEquals(Carbon::today()->toDateString(), $appointment->getDate()->toDateString());
        $this->assertEquals(Carbon::today()->toDateString(), $appointment->getDateCompleted()->toDateString());
        $this->assertTrue($appointment->isInitial());
        $this->assertEquals(TestValue::MIN_LATITUDE, $appointment->getLocation()->getLatitude());
        $this->assertEquals(TestValue::MIN_LONGITUDE, $appointment->getLocation()->getLongitude());
        $this->assertEquals(TestValue::CUSTOMER_ID, $appointment->getCustomer()->getId());
        $this->assertEquals(30, $appointment->getDuration()->getTotalMinutes());
    }
}
