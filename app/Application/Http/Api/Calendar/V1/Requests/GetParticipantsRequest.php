<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class GetParticipantsRequest extends AbstractFormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|int|gt:0',
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
