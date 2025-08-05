<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SharedKernel\Entities;

use Tests\TestCase;
use App\Domain\SharedKernel\Entities\Employee;
use App\Domain\SharedKernel\Entities\Skill;
use App\Domain\SharedKernel\Entities\WorkPeriod;
use App\Domain\SharedKernel\ValueObjects\Address;
use Illuminate\Support\Collection;

class EmployeeTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_employee_with_correct_attributes(): void
    {
        $skills = new Collection([new Skill('Management'), new Skill('Driving Development')]);
        $address = new Address('123 Main St', 'Springfield', 'State', '12345');
        $workPeriod = new WorkPeriod('M-F 9am-5pm');

        $employee = new Employee(
            'E001',
            'John',
            'Doe',
            '1980-01-01',
            '2020-01-01',
            'M001',
            'john.doe@example.com',
            '123-456-7890',
            $address,
            $workPeriod,
            $skills
        );

        $this->assertEquals('E001', $employee->getEmployeeId());
        $this->assertEquals('John', $employee->getFirstName());
        $this->assertEquals('Doe', $employee->getLastName());
        $this->assertEquals('1980-01-01', $employee->getDateOfBirth());
        $this->assertEquals('2020-01-01', $employee->getDateOfHire());
        $this->assertEquals('M001', $employee->getManagerId());
        $this->assertEquals('john.doe@example.com', $employee->getEmail());
        $this->assertEquals('123-456-7890', $employee->getPhone());
        $this->assertEquals($address, $employee->getAddress());
        $this->assertEquals($employee->getFirstName() . ' ' . $employee->getLastName(), $employee->getFullName());
        $this->assertEquals($workPeriod, $employee->getWorkPeriod());
        $this->assertCount(2, $employee->getSkills());
    }
}
