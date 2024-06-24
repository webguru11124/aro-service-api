<?php

declare(strict_types=1);

namespace App\Application\Http\Web\Notifications\Requests;

use App\Application\Http\Requests\AbstractFormRequest;
use App\Domain\Notification\Enums\NotificationChannel;
use Illuminate\Validation\Rule;

class UnsubscribeRecipientRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => 'required|int|gt:0',
            'notification_type_id' => 'required|int|gt:0',
            'channel' => [
                'required',
                'string',
                Rule::in(NotificationChannel::values()),
            ],
        ];
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'recipient_id' => $this->route()->parameter('recipient_id'),
            'notification_type_id' => $this->route()->parameter('notification_type_id'),
            'channel' => $this->route()->parameter('channel'),
        ]);
    }
}
