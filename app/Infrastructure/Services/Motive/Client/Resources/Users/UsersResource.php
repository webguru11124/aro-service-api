<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\Users;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractEntity;
use App\Infrastructure\Services\Motive\Client\Resources\AbstractResource;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\FindUserParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\SearchUsersParams;
use Illuminate\Support\Collection;

class UsersResource extends AbstractResource
{
    private const URL_PATH_FIND = 'v1/users/lookup';
    private const URL_PATH_SEARCH = 'v1/users';

    /**
     * @param FindUserParams $params
     *
     * @return AbstractEntity|User|null
     */
    public function find(FindUserParams $params): AbstractEntity|User|null
    {
        $endpoint = $this->getBaseUrl() . self::URL_PATH_FIND;

        try {
            $mapCallback = function (object $object) {
                return User::fromApiObject($object->user);
            };

            return $this->get($endpoint, $mapCallback, $params);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @param SearchUsersParams $params
     *
     * @return Collection<int, User>
     */
    public function search(SearchUsersParams $params): Collection
    {
        $mapCallback = function (object $object) {
            return Collection::make(array_map(
                fn (object $userObject) => User::fromApiObject($userObject->user),
                $object->users
            ));
        };

        return $this->getWithPagination(
            endpoint: $this->getBaseUrl() . self::URL_PATH_SEARCH,
            mapCallback: $mapCallback,
            params: $params,
        );
    }
}
