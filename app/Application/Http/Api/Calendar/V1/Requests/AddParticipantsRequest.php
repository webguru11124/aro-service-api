<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

/**
 * @property string $event_id
 * @property array $participant_ids
 */
class AddParticipantsRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|int|gt:0',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'int|gt:0',
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
