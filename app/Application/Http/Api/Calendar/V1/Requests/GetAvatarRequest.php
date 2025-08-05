<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class GetAvatarRequest extends AbstractFormRequest
{
    public function rules(): array
    {
        return [
            'external_id' => 'required|string',
        ];
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'external_id' => $this->route()->parameter('external_id'),
        ]);
    }
}
