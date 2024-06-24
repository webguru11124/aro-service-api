<?php

declare(strict_types=1);

namespace App\Application\Commands\ResetPreferredTech;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Application\Events\PreferredTechResigned;
use App\Domain\Scheduling\ValueObjects\ResignedTechAssignment;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\CustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SubscriptionsDataProcessor;

class ResetPreferredTechHandler
{
    public function __construct(
        private readonly CustomersDataProcessor $customersDataProcessor,
        private readonly SubscriptionsDataProcessor $subscriptionsDataProcessor,
    ) {
    }

    /**
     * Handle to reset the preferred service pro for the customers with resigned service pro to default.
     *
     * @param ResetPreferredTechCommand $command
     *
     * @return void
     */
    public function handle(ResetPreferredTechCommand $command): void
    {
        $this->triggerEventResetPreferredTech($command);

        /** @var ResignedTechAssignment $resignedTechAssignment */
        foreach ($command->resignedTechAssignments as $resignedTechAssignment) {
            $this->resetCustomerPreferredTech(
                $command->officeId,
                $resignedTechAssignment->customerId,
            );
            $this->resetSubscriptionPreferredTech(
                $command->officeId,
                $resignedTechAssignment->subscriptionId,
                $resignedTechAssignment->customerId
            );
        }
    }

    private function resetCustomerPreferredTech(int $officeId, int $customerId): void
    {
        try {
            $this->customersDataProcessor->resetPreferredTech(
                $officeId,
                $customerId,
            );
        } catch (Exception $exception) {
            Log::notice(__('messages.reset_preferred_tech.failed_reset_customer', [
                'office_id' => $officeId,
                'customer_id' => $customerId,
                'error' => $exception->getMessage(),
            ]));
        }
    }

    private function resetSubscriptionPreferredTech(int $officeId, int $subscriptionId, int $customerId): void
    {
        try {
            $this->subscriptionsDataProcessor->resetPreferredTech(
                $officeId,
                $subscriptionId,
                $customerId,
            );
        } catch (Exception $exception) {
            Log::notice(__('messages.reset_preferred_tech.failed_reset_subscription', [
                'office_id' => $officeId,
                'subscription_id' => $subscriptionId,
                'customer_id' => $customerId,
                'error' => $exception->getMessage(),
            ]));
        }
    }

    private function triggerEventResetPreferredTech(ResetPreferredTechCommand $command): void
    {
        PreferredTechResigned::dispatch(
            $command->resignedTechAssignments,
            $command->officeId,
        );
    }
}
