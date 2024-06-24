<?php

declare(strict_types=1);

namespace App\Application\Http\Web\OptimizationOverview\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class OptimizationSimulationRunRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state_id' => 'required|integer',
            'rule_name' => 'sometimes|array',
            'rule_name.*' => 'string',
            'rule_trigger' => 'sometimes|array',
            'rule_trigger.*' => 'string',
        ];
    }
}
