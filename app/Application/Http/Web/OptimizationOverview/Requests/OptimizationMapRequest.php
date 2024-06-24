<?php

declare(strict_types=1);

namespace App\Application\Http\Web\OptimizationOverview\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class OptimizationMapRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state_id' => 'required|integer|gt:0',
        ];
    }
}
