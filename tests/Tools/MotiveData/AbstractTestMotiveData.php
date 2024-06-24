<?php

declare(strict_types=1);

namespace Tests\Tools\MotiveData;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractEntity;
use Illuminate\Support\Collection;

/**
 * @template MotiveEntity of AbstractEntity
 */
abstract class AbstractTestMotiveData
{
    abstract protected static function getSignature(): array;

    /**
     * @return class-string<MotiveEntity>
     */
    abstract protected static function getRequiredEntityClass(): string;

    /**
     * @param int $objectsQuantity
     * @param array<string, mixed> ...$substitutions
     *
     * @return Collection<int, MotiveEntity>
     */
    public static function getTestData(int $objectsQuantity = 1, array ...$substitutions): Collection
    {
        $collection = static::getRawTestData($objectsQuantity, ...$substitutions);
        $entityClass = static::getRequiredEntityClass();

        return $collection->map(fn ($data) => $entityClass::fromApiObject((object) $data));
    }

    /**
     * @param int $objectsQuantity
     * @param array<string, mixed> ...$substitutions
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function getRawTestData(int $objectsQuantity = 1, array ...$substitutions): Collection
    {
        $collection = new Collection();

        for ($i = 0; $i < $objectsQuantity; $i++) {
            $substitution = $substitutions[$i] ?? [];
            $data = array_merge(static::getSignature(), $substitution);

            $collection->add($data);
        }

        return $collection;
    }
}
