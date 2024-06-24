<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Resources;

use App\Domain\Calendar\Entities\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarGetEmployeesResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Employee $employee */
        $employee = $this->resource;

        return [
            'id' => $employee->getId(),
            'name' => $employee->getName(),
            'external_id' => $employee->getWorkdayId(),
        ];
    }
}
