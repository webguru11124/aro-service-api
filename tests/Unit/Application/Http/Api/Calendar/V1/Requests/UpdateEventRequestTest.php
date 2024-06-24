<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\UpdateEventRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class UpdateEventRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new UpdateEventRequest();
    }

    public static function getInvalidData(): iterable
    {
        return [
            'title is empty' => [
                [
                    'title' => '',
                ],
            ],
            'title is too long' => [
                [
                    'title' => str_repeat('a', 101),
                ],
            ],
            'description is too long' => [
                [
                    'title' => 'title',
                    'description' => str_repeat('a', 501),
                ],
            ],
            'location_lat is not numeric' => [
                [
                    'title' => 'title',
                    'location_lat' => 'not numeric',
                ],
            ],
            'location_lng is not numeric' => [
                [
                    'title' => 'title',
                    'location_lng' => 'not numeric',
                ],
            ],
            'meeting_link is not a valid Google Meet link' => [
                [
                    'title' => 'title',
                    'meeting_link' => 'not a valid Google Meet link',
                ],
            ],
            'state is too long' => [
                [
                    'title' => 'title',
                    'state' => 'too long',
                ],
            ],
            'zip is too long' => [
                [
                    'title' => 'title',
                    'zip' => 'too long',
                ],
            ],
        ];
    }

    public static function getValidData(): iterable
    {
        return [
            'all fields are valid' => [
                [
                    'title' => 'title',
                    'description' => 'description',
                    'location_lat' => 1.0,
                    'location_lng' => 1.0,
                    'meeting_link' => 'https://meet.google.com/1234567890',
                    'address' => 'address',
                    'city' => 'city',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
            ],
        ];
    }
}
