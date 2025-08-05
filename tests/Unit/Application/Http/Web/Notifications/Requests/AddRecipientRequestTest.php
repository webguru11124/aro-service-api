<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Web\Notifications\Requests;

use App\Application\Http\Web\Notifications\Requests\AddRecipientRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class AddRecipientRequestTest extends AbstractRequestTest
{
    private const VALID_PARAMS = [
        'name' => 'John Doe',
        'email' => 'test@meail.com',
        'phone' => '1234567890',
    ];

    public function getTestedRequest(): Request
    {
        return new AddRecipientRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'missing_required_fields' => [
                [],
            ],
            'invalid_name' => [
                array_merge(self::VALID_PARAMS, ['name' => '']),
            ],
            'invalid_email_format' => [
                array_merge(self::VALID_PARAMS, ['email' => 'invalid']),
            ],
            'invalid_phone_length' => [
                array_merge(self::VALID_PARAMS, ['phone' => '12345678901']),
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
