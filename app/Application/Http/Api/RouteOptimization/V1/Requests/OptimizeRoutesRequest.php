<?php

declare(strict_types=1);

namespace App\Application\Http\Api\RouteOptimization\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class OptimizeRoutesRequest extends AbstractFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'office_ids' => 'required|array',
            'office_ids.*' => 'int|gt:0',
            'start_date' => 'sometimes|date|date_format:Y-m-d',
            'num_days_after_start_date' => 'sometimes|int|gt:0',
            'num_days_to_optimize' => 'sometimes|int|gt:0|lt:8',
            'last_optimization_run' => 'sometimes|boolean',
            'simulation_run' => 'sometimes|boolean',
            'build_planned_optimization' => 'sometimes|boolean',
            'disabled_rules' => 'sometimes|array',
            'disabled_rules.*' => 'string|distinct',
        ];
    }
}
