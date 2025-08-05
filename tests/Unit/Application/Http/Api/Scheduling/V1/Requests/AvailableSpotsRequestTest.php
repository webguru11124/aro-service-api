<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Requests;

use App\Application\Http\Api\Scheduling\V1\Requests\AvailableSpotsRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class AvailableSpotsRequestTest extends AbstractRequestTest
{
    private const VALID_DATA = [
        'office_id' => TestValue::OFFICE_ID,
        'lat' => TestValue::LATITUDE,
        'lng' => TestValue::LONGITUDE,
        'is_initial' => 0,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-30',
        'distance_threshold' => 10,
        'limit' => 2,
    ];

    public function getTestedRequest(): Request
    {
        return new AvailableSpotsRequest();
    }

    public static function getInvalidData(): iterable
    {
        yield [array_diff_key(self::VALID_DATA, [
            'office_id' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'lat' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'lng' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'is_initial' => null,
        ])];

        yield [array_merge(self::VALID_DATA, [
            'start_date' => '2023-10-011',
        ])];

        yield [array_merge(self::VALID_DATA, [
            'end_date' => '202310-01',
        ])];

        yield [array_merge(self::VALID_DATA, [
            'distance_threshold' => 0,
        ])];

        yield [array_merge(self::VALID_DATA, [
            'limit' => 0,
        ])];
    }

    public static function getValidData(): iterable
    {
        return [
            'valid_request_data' => [
                self::VALID_DATA,
            ],
        ];
    }
}
