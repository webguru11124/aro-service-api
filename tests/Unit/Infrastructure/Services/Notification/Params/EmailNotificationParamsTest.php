<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\Params;

use App\Infrastructure\Services\Notification\Params\EmailNotificationParams;
use Tests\TestCase;

class EmailNotificationParamsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_valid_object(): void
    {
        $employee = new EmailNotificationParams(
            toEmails: [$this->faker->email],
            fromEmail: $this->faker->email,
            subject: 'test subject',
            body: 'test body',
        );

        $this->assertEquals([$employee->toEmails[0]], $employee->toEmails);
        $this->assertEquals('test subject', $employee->subject);
        $this->assertEquals('test body', $employee->body);
        $this->assertEquals($employee->fromEmail, $employee->fromEmail);
        $this->assertEquals('email', $employee->type);
        $this->assertEquals('basicTemplate', $employee->emailTemplate);
    }
}
