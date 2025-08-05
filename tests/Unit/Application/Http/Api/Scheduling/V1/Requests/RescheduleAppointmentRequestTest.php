<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Requests;

use App\Application\Http\Api\Scheduling\V1\Requests\RescheduleAppointmentRequest;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use App\Infrastructure\Services\PestRoutes\Enums\ServiceType;
use App\Infrastructure\Services\PestRoutes\Enums\Window;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class RescheduleAppointmentRequestTest extends AbstractRequestTest
{
    private const VALID_DATA = [
        'office_id' => TestValue::OFFICE_ID,
        'customer_id' => TestValue::CUSTOMER_ID,
        'spot_id' => TestValue::SPOT_ID,
        'subscription_id' => TestValue::SUBSCRIPTION_ID,
        'current_appt_type' => ServiceType::BASIC->value,
        'window' => Window::AM->value,
        'requesting_source' => RequestingSource::TEST->value,
        'execution_sid' => 'TESTEXSID',
        'notes' => 'Test notes',
        'is_aro_spot' => 0,
    ];

    public function getTestedRequest(): Request
    {
        return new RescheduleAppointmentRequest();
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
            'current_appt_type' => null,
        ])];

        yield [array_diff_key(self::VALID_DATA, [
            'current_appt_type' => 111,
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
