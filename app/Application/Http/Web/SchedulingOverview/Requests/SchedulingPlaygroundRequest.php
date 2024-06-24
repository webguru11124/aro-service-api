<?php

declare(strict_types=1);

namespace App\Application\Http\Web\SchedulingOverview\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class SchedulingPlaygroundRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state_id' => 'sometimes|int|gt:0',
        ];
    }
}
