<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\Scheduling\ValueObjects\CustomerPreferences;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription as PestRoutesSubscription;

class PestRoutesCustomerPreferencesTranslator
{
    public function toDomain(PestRoutesSubscription $subscription): CustomerPreferences
    {
        return new CustomerPreferences(
            preferredStart: $subscription->preferredStart,
            preferredEnd: $subscription->preferredEnd,
            preferredEmployeeId: $subscription->preferredTechId,
            preferredDay: $subscription->preferredDay?->value,
        );
    }
}
