<?php

declare(strict_types=1);

namespace App\Application\Http\Api\RouteOptimization\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class SplitOptimizationStateRequest extends AbstractFormRequest
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
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d',
            'force_update' => 'required|boolean',
            'office_ids' => 'sometimes|array',
            'office_ids.*' => 'int|gt:0',
        ];
    }
}
