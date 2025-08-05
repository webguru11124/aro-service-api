<?php

declare(strict_types=1);

namespace App\Application\Http\Web\OptimizationOverview\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class OptimizationDetailsRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state_id' => 'required|integer|gt:0',
            'sim_state_id' => 'sometimes|integer|gt:0',
        ];
    }
}
