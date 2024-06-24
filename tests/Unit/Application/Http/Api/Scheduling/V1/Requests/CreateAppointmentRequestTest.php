<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Requests;

use App\Application\Http\Api\Scheduling\V1\Requests\CreateAppointmentRequest;
use App\Infrastructure\Services\PestRoutes\Enums\AppointmentType;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class CreateAppointmentRequestTest extends AbstractRequestTest
{
    private const VALID_DATA = [
        'office_id' => TestValue::OFFICE_ID,
        'customer_id' => TestValue::CUSTOMER_ID,
        'spot_id' => TestValue::SPOT_ID,
        'subscription_id' => TestValue::SUBSCRIPTION_ID,
        'appointment_type' => AppointmentType::BASIC->value,
        'window' => 'AM',
        'requesting_source' => RequestingSource::TEST->value,
        'execution_sid' => 'TESTEXSID',
        'notes' => 'Test notes',
        'is_aro_spot' => 0,
    ];

    public function getTestedRequest(): Request
    {
        return new CreateAppointmentRequest();
    }

    public static function getInvalidData(): iterable
    {
        yield [array_diff_key(self::VALID_DATA, [
            'office_id' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'customer_id' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'spot_id' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'subscription_id' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'appointment_type' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'appointment_type' => 7,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'window' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'window' => 'AMG',
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'requesting_source' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'requesting_source' => 'CXPA',
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'is_aro_spot' => null,
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
