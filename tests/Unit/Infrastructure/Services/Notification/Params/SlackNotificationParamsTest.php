<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\Params;

use App\Infrastructure\Services\Notification\Params\SlackNotificationParams;
use Tests\TestCase;

class SlackNotificationParamsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_valid_object(): void
    {
        $employee = new SlackNotificationParams(
            body: 'test',
        );

        $this->assertEquals('test', $employee->body);
    }
}
