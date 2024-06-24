<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\Senders;

use App\Infrastructure\Services\Notification\Senders\NotificationSenderParams;
use Tests\TestCase;
use Tests\Tools\Factories\Notification\SubscriptionFactory;

class NotificationSenderParamsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_valid_object(): void
    {
        $params = new NotificationSenderParams(
            title: 'test title',
            message: 'test message',
            recipients: collect([SubscriptionFactory::make()]),
        );

        $this->assertEquals('test title', $params->title);
        $this->assertEquals('test message', $params->message);
        $this->assertEquals(1, $params->recipients->count());
    }
}
