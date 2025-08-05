<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\GetParticipantsRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class GetParticipantsRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new GetParticipantsRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'event_id_must_be_an_integer' => [
                [
                    'event_id' => 'test',
                ],
            ],
            'event_id_must_greater_than_0' => [
                [
                    'event_id' => 0,
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data' => [
                [
                    'event_id' => TestValue::EVENT_ID,
                ],
            ],
        ];
    }
}
