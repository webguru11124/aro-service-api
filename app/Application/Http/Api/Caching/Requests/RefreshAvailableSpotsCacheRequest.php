<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Caching\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class RefreshAvailableSpotsCacheRequest extends AbstractFormRequest
{
    /**
     * Get the validation rules for the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'office_ids' => 'sometimes|array',
            'office_ids.*' => 'int|gt:0',
            'start_date' => 'sometimes|date|date_format:Y-m-d',
            'end_date' => 'sometimes|date|date_format:Y-m-d',
            'ttl' => 'sometimes|int|gt:0',
        ];
    }
}
