<?php

declare(strict_types=1);

namespace App\Application\Http\Api\WebHooks\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class UpdateCustomerPropertyDetailsRequest extends AbstractFormRequest
{
    /**
     * Get the validation rules for the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|int',
            'land_sqft' => 'required|numeric',
            'building_sqft' => 'required|numeric',
            'living_sqft' => 'required|numeric',
        ];
    }
}
