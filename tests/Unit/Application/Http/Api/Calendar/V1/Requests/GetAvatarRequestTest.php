<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\GetAvatarRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class GetAvatarRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new GetAvatarRequest();
    }

    public static function getInvalidData(): iterable
    {
        return [
            'external_id_must_be_non_empty_string' => [
                [
                    'external_id' => '',
                ],
            ],
        ];
    }

    public static function getValidData(): iterable
    {
        return [
            'valid_request_data' => [
                [
                    'external_id' => TestValue::WORKDAY_ID,
                ],
            ],
        ];
    }
}
