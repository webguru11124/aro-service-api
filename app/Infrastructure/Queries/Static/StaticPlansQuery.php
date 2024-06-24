<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Static;

use App\Domain\Contracts\Queries\PlansQuery;
use App\Domain\Scheduling\Entities\Plan;
use Illuminate\Support\Collection;

class StaticPlansQuery implements PlansQuery
{
    public const BASIC_ID = 4;
    public const PRO_ID = 1;
    public const PRO_PLUS_ID = 2;
    public const PREMIUM_ID = 3;

    public const BASIC = 'Basic';
    public const PREMIUM = 'Premium';
    public const PRO = 'Pro';
    public const PRO_PLUS = 'Pro +';

    public const BASIC_PEST_ROUTES_ID = 1799;
    public const PRO_PEST_ROUTES_ID = 2827;
    public const PRO_PLUS_PEST_ROUTES_ID = 1800;
    public const PREMIUM_PEST_ROUTES_ID = 2828;

    public const PLANS = [
        self::BASIC_ID => [
            'id' => self::BASIC_ID,
            'name' => self::BASIC,
            'service_type_id' => self::BASIC_PEST_ROUTES_ID,
            'services_per_year' => 4,
            'summer_service_interval_days' => 50,
            'winter_service_interval_days' => 50,
            'summer_service_period_days' => 30,
            'winter_service_period_days' => 30,
        ],
        self::PRO_ID => [
            'id' => self::PRO_ID,
            'name' => self::PRO,
            'service_type_id' => self::PRO_PEST_ROUTES_ID,
            'services_per_year' => 6,
            'summer_service_interval_days' => 30,
            'winter_service_interval_days' => 50,
            'summer_service_period_days' => 30,
            'winter_service_period_days' => 30,
        ],
        self::PRO_PLUS_ID => [
            'id' => self::PRO_PLUS_ID,
            'name' => self::PRO_PLUS,
            'service_type_id' => self::PRO_PLUS_PEST_ROUTES_ID,
            'services_per_year' => 6,
            'summer_service_interval_days' => 30,
            'winter_service_interval_days' => 50,
            'summer_service_period_days' => 30,
            'winter_service_period_days' => 30,
        ],
        self::PREMIUM_ID => [
            'id' => self::PREMIUM_ID,
            'name' => self::PREMIUM,
            'service_type_id' => self::PREMIUM_PEST_ROUTES_ID,
            'services_per_year' => 8,
            'summer_service_interval_days' => 20,
            'winter_service_interval_days' => 50,
            'summer_service_period_days' => 20,
            'winter_service_period_days' => 30,
        ],
    ];

    public const INITIAL_FOLLOW_UP_INTERVAL_DAYS = 30;

    /**
     * Returns list of active plans
     *
     * @return Collection<Plan>
     */
    public function get(): Collection
    {
        $plans = new Collection();

        foreach (self::PLANS as $planData) {
            $plans->add(new Plan(
                $planData['id'],
                $planData['name'],
                $planData['service_type_id'],
                $planData['summer_service_interval_days'],
                $planData['winter_service_interval_days'],
                $planData['summer_service_period_days'],
                $planData['winter_service_period_days'],
                self::INITIAL_FOLLOW_UP_INTERVAL_DAYS,
            ));
        }

        return $plans;
    }
}
