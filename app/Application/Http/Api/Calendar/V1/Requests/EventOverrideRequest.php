<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;
use App\Application\Http\Rules\NativeBoolean;
use Illuminate\Validation\Validator;

class EventOverrideRequest extends AbstractFormRequest
{
    /**
     * Get the validation rules for the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date|date_format:Y-m-d',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'is_canceled' => ['sometimes', new NativeBoolean()],
            'start_time' => 'required_with:end_time|date_format:H:i:s',
            'end_time' => 'required_with:start_time|date_format:H:i:s',
            'location_lat' => 'required_with:location_lng|numeric',
            'location_lng' => 'required_with:location_lat|numeric',
            'meeting_link' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
        ];
    }

    /**
     * Additional validation rules after the request passes the initial validation.
     *
     * @return array<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $startTime = $this->get('start_time');
                $endTime = $this->get('end_time');

                if ($startTime !== null && $endTime !== null && strtotime($endTime) <= strtotime($startTime)) {
                    $validator->errors()->add('end_time', 'The end time must be greater than start time.');
                }
            },
        ];
    }
}
