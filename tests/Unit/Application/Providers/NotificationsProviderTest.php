<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Providers;

use App\Application\Listeners\Notifications\SendOptimizationFailedNotification;
use App\Application\Listeners\Notifications\SendRoutesExcludedNotification;
use App\Application\Listeners\Notifications\SendOptimizationSkippedNotification;
use App\Application\Listeners\Notifications\SendSchedulingFailedNotification;
use App\Application\Listeners\Notifications\SendSchedulingSkippedNotification;
use App\Application\Providers\NotificationsProvider;
use App\Infrastructure\Services\Notification\Senders\EmailNotificationSender;
use App\Infrastructure\Services\Notification\Senders\NotificationSender;
use App\Infrastructure\Services\Notification\Senders\SmsNotificationSender;
use Illuminate\Container\Container;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class NotificationsProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const array NOTIFICATION_SENDERS = [
        SmsNotificationSender::class,
        EmailNotificationSender::class,
    ];

    private const array NOTIFICATION_SUBJECTS = [
        SendOptimizationFailedNotification::class,
        SendOptimizationSkippedNotification::class,
        SendSchedulingFailedNotification::class,
        SendSchedulingSkippedNotification::class,
        SendRoutesExcludedNotification::class,
    ];

    /**
     * @test
     */
    public function it_registers_notification_senders_correctly(): void
    {
        $app = Mockery::mock(Container::class);
        $provider = new NotificationsProvider($app);

        $sendersMocks = [];

        foreach (self::NOTIFICATION_SENDERS as $senderClass) {
            $senderMock = Mockery::mock($senderClass);
            $app->shouldReceive('make')
                ->with($senderClass)
                ->andReturn($senderMock)
                ->once();
            $sendersMocks[] = $senderMock;
        }

        foreach (self::NOTIFICATION_SUBJECTS as $subject) {
            $app->shouldReceive('when')
                ->with($subject)
                ->andReturnSelf()
                ->once();
            $app->shouldReceive('needs')
                ->with(NotificationSender::class)
                ->andReturnSelf()
                ->once();
            $app->shouldReceive('give')
                ->with(
                    Mockery::on(function ($callback) use ($app, $sendersMocks) {
                        $resolvedSenders = $callback($app);

                        return count($resolvedSenders) === 2
                            && $resolvedSenders[0] === $sendersMocks[0]
                            && $resolvedSenders[1] === $sendersMocks[1];
                    })
                )
                ->andReturnNull()
                ->once();
        }

        $provider->register();
    }
}
