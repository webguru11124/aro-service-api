<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class FleetRoutesRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'office_id' => 'required|int',
            'date' => 'required|date|date_format:Y-m-d',
        ];

    }
}
