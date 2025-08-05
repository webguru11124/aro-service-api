<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\EventOverrideRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use Illuminate\Support\Facades\Validator;

class EventOverrideRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new EventOverrideRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'date_is_required' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                ],
            ],
            'date_must_be_a_valid_date_format' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => 'test',
                ],
            ],
            'title_must_be_a_string' => [
                [
                    'title' => 1,
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lat' => 1.0,
                    'location_lng' => 1.0,
                ],
            ],
            'description_must_be_a_string' => [
                [
                    'title' => 'test',
                    'description' => 1,
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lat' => 1.0,
                    'location_lng' => 1.0,
                ],
            ],
            'is_canceled_must_be_a_boolean' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => 1,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lat' => 1.0,
                    'location_lng' => 1.0,
                ],
            ],
            'start_time_is_required_when_end_time_provided' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'end_time' => '00:00:00',
                    'date' => '2021-01-01',
                ],
            ],
            'start_time_must_be_a_valid_time_format' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => 'test',
                    'end_time' => '00:00:00',
                    'date' => '2021-01-01',
                ],
            ],
            'end_time_is_required_when_start_time_provided' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'date' => '2021-01-01',
                ],
            ],
            'end_time_must_be_a_valid_time_format' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => 'test',
                    'date' => '2021-01-01',
                ],
            ],
            'location_lat_must_be_a_float' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lat' => 'test',
                    'location_lng' => 1.0,
                ],
            ],
            'location_lat_is_required_with_location_lng' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lat' => 1.0,
                ],
            ],
            'location_lng_must_be_a_float' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lat' => 1.0,
                    'location_lng' => 'test',
                ],
            ],
            'location_lng_is_required_with_location_lat' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lng' => 1.0,
                ],
            ],
            'meeting_link_must_be_string' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lng' => 1.0,
                    'meeting_link' => 1.0,
                ],
            ],
            'address_must_be_string' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lng' => 1.0,
                    'address' => 1.0,
                ],
            ],
            'city_must_be_string' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lng' => 1.0,
                    'city' => 1.0,
                ],
            ],
            'state_must_be_string' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lng' => 1.0,
                    'state' => 1.0,
                ],
            ],
            'zip_must_be_string' => [
                [
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'date' => '2021-01-01',
                    'location_lng' => 1.0,
                    'zip' => 1.0,
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data with required fields' => [
                [
                    'date' => '2021-01-01',
                ],
            ],
            'valid_request_data with optional fields' => [
                [
                    'date' => '2021-01-01',
                    'title' => 'test',
                    'description' => 'test',
                    'is_canceled' => true,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:10',
                    'location_lat' => 1.0,
                    'location_lng' => 1.0,
                ],
            ],
            'valid_request_data with nullable fields' => [
                [
                    'date' => '2021-01-01',
                    'title' => null,
                    'description' => 'test',
                    'meeting_link' => null,
                    'address' => null,
                    'city' => null,
                    'state' => null,
                    'zip' => null,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_adds_error_when_end_time_is_before_start_time()
    {
        $request = new EventOverrideRequest();

        $request->offsetSet('start_time', '08:00:00');
        $request->offsetSet('end_time', '07:00:00');
        $request->offsetSet('date', '2021-01-01');
        $validator = Validator::make($request->toArray(), $request->rules());

        $request->after()[0]($validator);

        $this->assertTrue($validator->errors()->has('end_time'));
        $this->assertEquals(['The end time must be greater than start time.'], $validator->errors()->get('end_time'));
    }

    /**
     * @test
     */
    public function it_does_not_add_error_when_end_time_is_after_start_time()
    {
        $request = new EventOverrideRequest();

        $request->offsetSet('start_time', '08:00:00');
        $request->offsetSet('end_time', '09:00:00');
        $request->offsetSet('date', '2021-01-01');
        $validator = Validator::make($request->toArray(), $request->rules());

        $request->after()[0]($validator);

        $this->assertFalse($validator->errors()->has('end_time'));
    }
}
