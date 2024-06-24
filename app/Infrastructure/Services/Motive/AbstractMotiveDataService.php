<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive;

use App\Infrastructure\Services\Motive\Client\Client;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\FindUserParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\SearchUsersParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\User;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserRole;
use Illuminate\Support\Collection;

class AbstractMotiveDataService
{
    private const CACHE_USERS_TTL = 86400;

    public function __construct(
        protected readonly Client $client
    ) {
    }

    /**
     * Returns mapped array 'aptive employee ID' => 'motive user ID'
     *
     * @param string[] $companyUserIds
     *
     * @return array<string, int>
     */
    protected function preloadDrivers(array $companyUserIds): array
    {
        $page = 1;
        $params = new SearchUsersParams(
            role: UserRole::DRIVER
        );

        $allDriverIds = [];

        do {
            $params->setPage($page++);
            /** @var Collection<User> $drivers @phpstan-ignore-next-line*/
            $drivers = $this->client
                ->users()
                ->cached(self::CACHE_USERS_TTL)
                ->search($params);

            $filteredDrivers = $drivers->filter(fn (User $driver) => in_array($driver->companyId, $companyUserIds))->values();
            $filteredDrivers->each(function (User $driver) use (&$allDriverIds) {
                $findDriverParams = new FindUserParams(driverCompanyId: $driver->companyId);
                $this->client
                    ->users()
                    ->cached(self::CACHE_USERS_TTL)
                    ->preload($driver, 'find', $findDriverParams);

                $allDriverIds[$driver->companyId] = $driver->id;
            });
        } while (count($allDriverIds) < count($companyUserIds) && $drivers->isNotEmpty());

        /** @var array<string, int> $notFoundUserIds */
        $notFoundUserIds = array_diff_key(array_fill_keys($companyUserIds, null), $allDriverIds);

        foreach ($notFoundUserIds as $userId => $value) {
            $this->client
                ->users()
                ->cached(self::CACHE_USERS_TTL)
                ->preload($value, 'find', new FindUserParams(driverCompanyId: $userId));
        }

        return $allDriverIds;
    }
}
