<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Notifier;

use Mockery;
use Tests\TestCase;
use App\Application\Events\PreferredTechResigned;
use Tests\Tools\Factories\Scheduling\CustomerFactory;
use App\Domain\Scheduling\ValueObjects\ResignedTechAssignment;
use App\Application\Listeners\Notifier\PreferredTechUnavailableNotifier;
use App\Domain\Scheduling\Entities\Customer;
use App\Infrastructure\Services\Notification\EmailNotification\EmailNotificationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\Tools\TestValue;

class PreferredTechUnavailableNotifierTest extends TestCase
{
    private PreferredTechUnavailableNotifier $notifier;
    private MockInterface|EmailNotificationService $notificationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = new PreferredTechUnavailableNotifier(
            $this->notificationServiceMock = Mockery::mock(EmailNotificationService::class),
        );
        Config::set('notification-service.recipients.from_email', 'fromEmail@example.com');
    }

    /**
     * @test
     */
    public function it_correctly_handles_notifications(): void
    {
        $subscriptionId = TestValue::SUBSCRIPTION_ID;
        $customer = CustomerFactory::make();
        $resignedTechAssignment = $this->createResignedTechAssignment($customer, $subscriptionId);
        $officeId = TestValue::OFFICE_ID;

        $event = new PreferredTechResigned(
            collect([$resignedTechAssignment]),
            $officeId,
        );

        Log::shouldReceive('notice')
            ->times(1)
            ->withArgs(
                function ($message, $context) use ($resignedTechAssignment, $officeId) {
                    $messageCheck = $message === __('messages.notification.unavailable_preferred_tech.log_email_notification');

                    $customersCheck = isset($context['customers'])
                        && ($context['customers'][0]['customer_id'] === $resignedTechAssignment->customerId)
                        && ($context['customers'][0]['name'] === $resignedTechAssignment->customerName)
                        && ($context['customers'][0]['email'] === $resignedTechAssignment->customerEmail)
                        && ($context['office_id'] === $officeId);

                    return $messageCheck && $customersCheck;
                }
            );
        $this->notificationServiceMock->shouldReceive('send')
            ->times(1)
            ->withArgs(function ($params) use ($resignedTechAssignment) {
                return in_array($resignedTechAssignment->customerEmail, $params->toEmails)
                    && $params->emailTemplate === 'basicTemplate'
                    && $params->type === 'email';
            });

        $this->notifier->handle($event);
    }

    /**
     * @test
     */
    public function it_stops_if_from_email_not_set(): void
    {
        Config::set('notification-service.recipients.from_email');
        $subscriptionId = 1002;
        $customer = CustomerFactory::make();
        $resignedTechAssignment = $this->createResignedTechAssignment($customer, $subscriptionId);

        $officeId = TestValue::OFFICE_ID;
        $event = new PreferredTechResigned(
            collect([$resignedTechAssignment]),
            $officeId,
        );

        Log::shouldReceive('error')
            ->times(1)
            ->with(__('messages.notification.from_email_not_set_in_config'));
        $this->notificationServiceMock->shouldReceive('send')
            ->never();

        $this->notifier->handle($event);
    }

    /**
     * @test
     */
    public function it_stops_if_customers_emails_are_empty(): void
    {
        $subscriptionId = 1002;
        $customer = CustomerFactory::make(['email' => '']);
        $resignedTechAssignment = $this->createResignedTechAssignment($customer, $subscriptionId);
        $officeId = TestValue::OFFICE_ID;
        $event = new PreferredTechResigned(
            collect([$resignedTechAssignment]),
            $officeId,
        );

        Log::shouldReceive('notice')
            ->times(1)
            ->with(__('messages.notification.unavailable_preferred_tech.no_customers_to_notify'));
        $this->notificationServiceMock->shouldReceive('send')
            ->never();

        $this->notifier->handle($event);
    }

    private function createResignedTechAssignment(Customer $customer, int $subscriptionId): ResignedTechAssignment
    {
        return new ResignedTechAssignment(
            $customer->getId(),
            $customer->getName(),
            $customer->getEmail(),
            $subscriptionId,
            $customer->getPreferredTechId(),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->notifier,
            $this->notificationServiceMock
        );
    }
}
