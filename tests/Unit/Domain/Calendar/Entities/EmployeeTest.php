<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\Entities;

use App\Domain\Calendar\Entities\Employee;
use Tests\TestCase;
use Tests\Tools\TestValue;

class EmployeeTest extends TestCase
{
    /** @test */
    public function it_returns_correct_employee(): void
    {
        $employee = new Employee(
            id: 1,
            name: 'John Doe',
            workdayId: TestValue::WORKDAY_ID,
        );

        $this->assertEquals(1, $employee->getId());
        $this->assertEquals('John Doe', $employee->getName());
        $this->assertEquals(TestValue::WORKDAY_ID, $employee->getWorkdayId());
    }
}
