<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Calendar;

use App\Domain\Calendar\Entities\Employee;
use Tests\Tools\Factories\AbstractFactory;

class EmployeeFactory extends AbstractFactory
{
    public function single($overrides = []): Employee
    {
        return new Employee(
            id: $overrides['id'] ?? $this->faker->randomNumber(),
            name: $overrides['name'] ?? $this->faker->name(),
            workdayId: $overrides['workdayId'] ?? $this->faker->word(),
        );
    }
}
