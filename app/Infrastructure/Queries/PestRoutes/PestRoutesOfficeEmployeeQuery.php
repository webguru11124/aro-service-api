<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Domain\Calendar\Entities\Employee;
use App\Domain\Contracts\Queries\Office\OfficeEmployeeQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Employees\EmployeeType;
use Illuminate\Support\Collection;

class PestRoutesOfficeEmployeeQuery implements OfficeEmployeeQuery
{
    public function __construct(
        private readonly PestRoutesEmployeesDataProcessor $pestRoutesEmployeesDataProcessor,
    ) {
    }

    /**
     * Fetches employees for the office by office ID.
     *
     * @param int $officeId
     *
     * @return Collection<Employee> of Employee entities
     */
    public function find(int $officeId): Collection
    {
        $officeStaff = $this->pestRoutesEmployeesDataProcessor->extract($officeId, new SearchEmployeesParams(
            officeIds: [$officeId],
            isActive: true,
            type: EmployeeType::OfficeStaff,
        ));

        $technicians = $this->pestRoutesEmployeesDataProcessor->extract($officeId, new SearchEmployeesParams(
            officeIds: [$officeId],
            isActive: true,
            type: EmployeeType::Technician,
        ));

        $employees = $officeStaff->merge($technicians);

        return $employees->map(function (PestRoutesEmployee $employee) {
            return new Employee(
                $employee->id,
                $employee->firstName . ' ' . $employee->lastName,
                $employee->employeeLink ?: null,
            );
        })->sortBy->getName();
    }
}
