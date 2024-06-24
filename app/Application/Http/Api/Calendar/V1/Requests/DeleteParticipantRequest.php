<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class DeleteParticipantRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'participant_id' => 'required|int|gt:0',
            'event_id' => 'required|int|gt:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationData(): array
    {
        return $this->route()->parameters();
    }
}
