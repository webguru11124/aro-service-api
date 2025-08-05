<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Web\Notifications\Requests;

use App\Application\Http\Web\Notifications\Requests\UnsubscribeRecipientRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class UnsubscribeRecipientRequestTest extends AbstractRequestTest
{
    private const VALID_PARAMS = [
        'recipient_id' => 123,
        'notification_type_id' => 123,
        'channel' => 'email',
    ];

    public function getTestedRequest(): Request
    {
        return new UnsubscribeRecipientRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'missing_required_fields' => [
                [],
            ],
            'invalid_recipient_id' => [
                array_merge(self::VALID_PARAMS, ['recipient_id' => 'invalid']),
            ],
            'invalid_notification_type_id' => [
                array_merge(self::VALID_PARAMS, ['notification_type_id' => 'invalid']),
            ],
            'invalid_channel' => [
                array_merge(self::VALID_PARAMS, ['channel' => 'invalid']),
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_with_minimum_required_fields' => [
                self::VALID_PARAMS,
            ],
        ];
    }
}
