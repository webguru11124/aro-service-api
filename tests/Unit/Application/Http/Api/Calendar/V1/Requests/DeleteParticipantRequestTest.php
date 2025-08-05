<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\DeleteParticipantRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class DeleteParticipantRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new DeleteParticipantRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'participant_id_must_be_an_integer' => [
                [
                    'participant_id' => 'test',
                ],
            ],
            'participant_id_must_greater_than_0' => [
                [
                    'participant_id' => 0,
                ],
            ],
            'participant_id_is_required' => [
                [
                ],
            ],
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
            'event_id_is_required' => [
                [
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data' => [
                [
                    'participant_id' => 82,
                    'event_id' => 82,
                ],
            ],
        ];
    }
}
