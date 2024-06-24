<?php

declare(strict_types=1);

namespace App\Application\Http\Web\ServiceDurationCalculation\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class ServiceDurationCalculationRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'calculateServiceDuration' => 'required_without:calculateLf',
            'calculateLf' => 'required_without:calculateServiceDuration',
            'linearFootPerSecond' => 'nullable|numeric|min:0.1',
            'squareFootageOfHouse' => 'required|numeric|min:1',
            'squareFootageOfLot' => 'required|numeric|min:1',
            'actualDuration' => 'required_if:calculateLf,1|nullable|numeric|min:1',
        ];
    }
}
