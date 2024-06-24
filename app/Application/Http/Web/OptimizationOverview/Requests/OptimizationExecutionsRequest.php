<?php

declare(strict_types=1);

namespace App\Application\Http\Web\OptimizationOverview\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class OptimizationExecutionsRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'execution_date' => 'sometimes|date|date_format:Y-m-d',
        ];
    }
}
