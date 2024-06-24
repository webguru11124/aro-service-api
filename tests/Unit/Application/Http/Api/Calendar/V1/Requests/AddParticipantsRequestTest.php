<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\AddParticipantsRequest;
use Illuminate\Http\Request;
use Override;
use Tests\Tools\AbstractRequestTest;

class AddParticipantsRequestTest extends AbstractRequestTest
{
    #[Override]
    public function getTestedRequest(): Request
    {
        return new AddParticipantsRequest();
    }

    #[Override]
    public static function getInvalidData(): array
    {
        return [
            'event_id_is_required' => [
                [
                    'participant_ids' => [1, 2, 3],
                ],
            ],
            'event_id_must_be_an_integer' => [
                [
                    'event_id' => 'test',
                    'participant_ids' => [1, 2, 3],
                ],
            ],
            'event_id_must_be_greater_than_zero' => [
                [
                    'event_id' => 0,
                    'participant_ids' => [1, 2, 3],
                ],
            ],
            'participant_ids_is_required' => [
                [
                    'event_id' => 1,
                ],
            ],
            'participant_ids_must_be_an_array' => [
                [
                    'event_id' => 1,
                    'participant_ids' => 'test',
                ],
            ],
            'participant_ids_must_be_an_array_of_integers' => [
                [
                    'event_id' => 1,
                    'participant_ids' => [1, 'test', 3],
                ],
            ],
            'participant_ids_must_be_an_array_of_integers_greater_than_zero' => [
                [
                    'event_id' => 1,
                    'participant_ids' => [1, 0, 3],
                ],
            ],
        ];
    }

    #[Override]
    public static function getValidData(): array
    {
        return [
            'valid_data' => [
                [
                    'event_id' => 1,
                    'participant_ids' => [1, 2, 3],
                ],
            ],
        ];
    }
}
