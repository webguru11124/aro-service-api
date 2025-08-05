<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class UpdateEventRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'location_lat' => 'nullable|numeric|required_with:location_lng',
            'location_lng' => 'nullable|numeric|required_with:location_lat',
            'meeting_link' => 'nullable|string|max:200|regex:/^(https?:\/\/)?meet\.google\.com\/[a-z0-9-]+$/',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string|size:2',
            'zip' => 'nullable|string|size:5',
        ];
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_id' => $this->route()->parameter('event_id'),
        ]);
    }
}
