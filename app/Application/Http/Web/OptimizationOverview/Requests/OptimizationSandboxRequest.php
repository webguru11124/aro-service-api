<?php

declare(strict_types=1);

namespace App\Application\Http\Web\OptimizationOverview\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class OptimizationSandboxRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'optimization_date' => 'sometimes|date|date_format:Y-m-d',
            'office_id' => 'sometimes|int|gt:0',
        ];
    }
}
