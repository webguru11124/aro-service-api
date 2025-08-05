<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Resources\CalendarGetEmployeesResource;
use App\Application\Http\Responses\AbstractResponse;
use App\Domain\Calendar\Entities\Employee;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Support\Collection;

class GetOfficeEmployeesResponse extends AbstractResponse
{
    /**
     * @param Collection<Employee> $employees
     */
    public function __construct(Collection $employees)
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);

        $this->setResult([
            'employees' => !$employees->isEmpty()
                ? CalendarGetEmployeesResource::collection($employees)
                : [],
        ]);
    }
}
