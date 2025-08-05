<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Notification\Entities;

use App\Domain\Notification\Entities\NotificationType;
use Tests\TestCase;

class NotificationTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_correctly_returns_notification_type_properties(): void
    {
        $id = 1;
        $type = 'failure';

        $notificationType = new NotificationType($id, $type);

        $this->assertEquals($id, $notificationType->getId());
        $this->assertEquals($type, $notificationType->getName());
    }
}
