<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class AvailableSpotsRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'office_id' => 'required|int',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'is_initial' => 'required|boolean',
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            'distance_threshold' => 'nullable|integer|gte:1',
            'limit' => 'nullable|int|gte:1',
        ];
    }
}
