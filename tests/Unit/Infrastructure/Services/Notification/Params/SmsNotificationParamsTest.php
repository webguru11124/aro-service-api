<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\Params;

use App\Infrastructure\Services\Notification\Params\SmsNotificationParams;
use Tests\TestCase;

class SmsNotificationParamsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_valid_object(): void
    {
        $employee = new SmsNotificationParams(
            smsData: 'test',
            toNumbers: ['123'],
        );

        $this->assertEquals('test', $employee->smsData);
        $this->assertEquals(['123'], $employee->toNumbers);
        $this->assertEquals('sms', $employee->type);
        $this->assertEquals('internalCommunication', $employee->smsBus);
    }
}
