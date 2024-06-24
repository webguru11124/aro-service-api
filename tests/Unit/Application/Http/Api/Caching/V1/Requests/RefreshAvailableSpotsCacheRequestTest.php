<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Caching\V1\Requests;

use App\Application\Http\Api\Caching\Requests\RefreshAvailableSpotsCacheRequest;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class RefreshAvailableSpotsCacheRequestTest extends AbstractRequestTest
{
    private const VALID_DATA = [
        'office_ids' => [TestValue::OFFICE_ID],
        'start_date' => '2023-07-23',
        'end_date' => '2023-07-26',
        'ttl' => 300,
    ];

    public function getTestedRequest(): RefreshAvailableSpotsCacheRequest
    {
        return new RefreshAvailableSpotsCacheRequest();
    }

    public static function getInvalidData(): iterable
    {
        yield 'office_id_must_be_an_integer' => [array_merge(self::VALID_DATA, [
            'office_ids' => ['a'],
        ])];

        yield 'office_id_must_be_greater_than_zero' => [array_merge(self::VALID_DATA, [
            'office_ids' => [0],
        ])];

        yield 'start_date_must_be_string' => [array_merge(self::VALID_DATA, [
            'start_date' => 1234,
        ])];

        yield 'start_date_must_be_in_valid_format' => [array_merge(self::VALID_DATA, [
            'start_date' => '10.10.2000',
        ])];

        yield 'end_date_must_be_string' => [array_merge(self::VALID_DATA, [
            'end_date' => 1234,
        ])];

        yield 'end_date_must_be_in_valid_format' => [array_merge(self::VALID_DATA, [
            'end_date' => '10.10.2000',
        ])];

        yield 'ttl_to_optimize_must_be_int' => [array_merge(self::VALID_DATA, [
            'ttl' => 'a',
        ])];

        yield 'ttl_to_optimize_must_be_greater_than_zero' => [array_merge(self::VALID_DATA, [
            'ttl' => 0,
        ])];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data_with_all_fields' => [
                self::VALID_DATA,
            ],
        ];
    }
}
